<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * @OA\Tag(
 *     name="Messages",
 *     description="API Endpoints for managing chat messages"
 * )
 */
class MessageController extends Controller
{
    protected $aiChatService;

    public function __construct(AiChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    /**
     * @OA\Get(
     *     path="/chats/{chat}/messages",
     *     tags={"Messages"},
     *     summary="Get chat messages",
     *     description="Retrieve all messages for a specific chat",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Messages retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                  property="data", type="array", 
     *                  @OA\Items(
     *                      type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="content", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="user_id", type="integer"),
     *                      @OA\Property(property="chat_id", type="integer"),
     *                      @OA\Property(property="is_read", type="boolean"),
     *                      @OA\Property(property="audio_path", type="string"),
     *                  )
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = $chat->messages()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(perPage: 20);

        return response()->json($messages);
    }

    /**
     * @OA\Post(
     *     path="/chats/{chat}/messages",
     *     tags={"Messages"},
     *     summary="Create a new message",
     *     description="Create a new message in a chat (text or audio)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"content", "type"},
     *                 @OA\Property(property="content", type="string", description="Message content or transcription"),
     *                 @OA\Property(property="type", type="string", enum={"text", "audio"}, description="Message type"),
     *                 @OA\Property(property="audio", type="file", format="binary", description="Audio file (required if type is audio)"),
     *                 @OA\Property(property="stream", type="boolean", description="Whether to stream the AI response")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                  property="message", type="object",
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="content", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="user_id", type="integer"),
     *                      @OA\Property(property="chat_id", type="integer"),
     *                      @OA\Property(property="is_read", type="boolean"),
     *                      @OA\Property(property="audio_path", type="string"),
     *              ),
     *             @OA\Property(property="ai_response", 
     *                  type="object", 
     *                  nullable=true, 
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="content", type="string"),
     *                      @OA\Property(property="type", type="string"),
     *                      @OA\Property(property="user_id", type="integer"),
     *                      @OA\Property(property="chat_id", type="integer"),
     *                      @OA\Property(property="is_read", type="boolean"),
     *                      @OA\Property(property="audio_path", type="string"),),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request, Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:text,audio',
            'audio_path' => 'required_if:type,audio|string',
            'stream' => 'boolean',
        ]);

        $message = new Message();
        $message->chat_id = $chat->id;
        $message->user_id = Auth::id();
        $message->type = $request->type;
        $message->content = $request->content;
        $message->is_read = false;
        $message->audio_path = $request->audio_path;
        $message->save();
        $chat->touch();

        if ($request->boolean('stream', false)) {
            return $this->streamAiResponse($chat, $message);
        } else {
            $aiResponse =  $this->aiChatService->generateResponse($chat, $message);
            return response()->json([
                'message' => $message->load('user'),
                'ai_response' => $aiResponse->load('user')
            ], 201);
        }
    }

    /**
     * @OA\Post(
     *     path="/chats/{chat}/messages/transcribe",
     *     summary="Transcribe audio to text",
     *     description="Transcribes an uploaded audio file and returns the transcription.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"audio"},
     *                 @OA\Property(property="audio", type="file", format="binary", description="Audio file"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Message created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *              @OA\Property(property="transcription", type="string"),
     *              @OA\Property(property="language", type="string"),
     *              @OA\Property(property="audio_path", type="string"),
     *        ),
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized access"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chat not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function transcribeAudio(Request $request, Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'audio' => 'required|file|max:10240',
        ]);
        $path = $request->file('audio')->store('audio_messages', 'public');
        try {
            $transcription = $this->aiChatService->transcribeAudio(Storage::disk('public')->path($path));
            return response()->json([
                'transcription' => $transcription['transcription'],
                'language' => $transcription['language'],
                'audio_path' => $path
            ], 200);
        } catch (\Exception $e) {
            Log::error('Transcription error: ' . $e->getMessage());
            return response()->json([
                'transcription' => 'Transcription failed',
                'language' => 'en',
                'audio_path' => $path
            ], status: 200);
        }
    }


    protected function streamAiResponse(Chat $chat, Message $message): StreamedResponse
    {
        $steamData = $this->aiChatService->generateResponse($chat, $message, true);
        return new StreamedResponse((function () use ($steamData, $chat) {
            try {
                $parts =  [];
                foreach ($steamData as $chunk) {
                    $chunkType = $this->aiChatService->mapChunkTypeToString($chunk->chunkType);
                    $chunkData = [
                        'chunkType' => $chunkType,
                        'content' => $chunk->text,
                    ];
                    yield json_encode($chunkData);
                    if ($chunkType == 'text') {
                        $parts[] = $chunk->text;
                    }
                }
                $this->aiChatService->saveAiMessage($chat->id, implode('', $parts));
            } catch (Throwable $throwable) {
                Log::error('Stream error  ' . $throwable->getMessage());
                yield json_encode([
                    'chunkType' => 'error',
                    'content' => 'Stream failde'
                ]);
            }
        }), 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
