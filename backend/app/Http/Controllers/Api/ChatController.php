<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Chats",
 *     description="API Endpoints for managing chat conversations"
 * )
 */
class ChatController extends Controller
{
    /**
     * @OA\Get(
     *     path="/chats",
     *     tags={"Chats"},
     *     summary="Get all active chats",
     *     description="Retrieve all active chats for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of chats retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 ),
     *                 @OA\Property(
     *                     property="messages",
     *                     type="array",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function index()
    {
        $chats = Chat::with(['user', 'messages' => function ($query) {
            $query->latest()->take(1);
        }])
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    /**
     * @OA\Post(
     *     path="/chats",
     *     tags={"Chats"},
     *     summary="Create a new chat",
     *     description="Create a new chat conversation for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", example="New Conversation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chat created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $chat = Chat::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'is_active' => true,
        ]);

        return response()->json($chat->load('user'), 201);
    }

    /**
     * @OA\Get(
     *     path="/chats/{chat}",
     *     tags={"Chats"},
     *     summary="Get chat details",
     *     description="Retrieve detailed information about a specific chat",
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
     *         description="Chat details retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             ),
     *             @OA\Property(
     *                 property="messages",
     *                 type="array",
     *                 @OA\Items(type="object")
     *             )
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
    public function show(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(data: $chat->load(['user', 'messages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }]));
    }

    /**
     * @OA\Delete(
     *     path="/chats/{chat}",
     *     tags={"Chats"},
     *     summary="Delete a chat",
     *     description="Delete a chat and all its associated messages",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="chat",
     *         in="path",
     *         required=true,
     *         description="Chat ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Chat deleted successfully"
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
    public function destroy(Chat $chat)
    {
        if ($chat->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete all messages and their associated audio files
        foreach ($chat->messages as $message) {
            if ($message->audio_path) {
                Storage::disk('public')->delete($message->audio_path);
            }
        }

        $chat->delete();

        return response()->json(null, 204);
    }

}
