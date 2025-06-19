<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\ChunkType;
use Prism\Prism\ValueObjects\Messages\AssistantMessage;
use Prism\Prism\ValueObjects\Messages\UserMessage;

class AiChatService
{
    protected PrismClient $prismClient;

    public function __construct(?PrismClient $prismClient = null)
    {
        $this->prismClient = $prismClient ?? new PrismClient();
    }

    public function generateResponse(Chat $chat, Message $message, bool $stream = false)
    {
        try {
            $context = $chat->messages()
                ->with('user')
                ->latest()
                ->take(5)
                ->get()
                ->reverse()
                ->map(fn(Message $msg): UserMessage|AssistantMessage => match ($msg->user_id) {
                    Auth::id() => new UserMessage(content: $msg->content ?? ''),
                    default => new AssistantMessage(content: $msg->content ?? ''),
                })
                ->toArray();

            $context[] = new UserMessage(content: $message->content);

            if ($stream) {
                return $this->prismClient->getStreamResponse($context);
            }

            $response = $this->prismClient->getAiResponse($context);
            return $this->saveAiMessage($chat->id, $response['content']);
        } catch (\Exception $e) {
            Log::error('AI Chat Error: ' . $e->getMessage());
            return $this->saveAiMessage($chat->id, "Failed to generate AI response: " . $e->getMessage());
        }
    }

    public function transcribeAudio(string $audioPath)
    {
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->attach(
                'file',
                file_get_contents($audioPath),
                basename($audioPath)
            )
            ->asMultipart()
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'response_format' => 'text',
                'model' => 'whisper-1',
            ]);

        if ($response->failed()) {
            throw new \Exception('Transcription failed: ' . $response->body());
        }
        $text = $response->body();
        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a language detector. Reply only with the ISO 639-1 two-letter language code of the input (like "fr", "en", "mg", "es", "de"). No explanation.'
                    ],
                    ['role' => 'user', 'content' => $text],
                ],
                'temperature' => 0,
            ]);

        $languageCode = trim($response->json('choices.0.message.content'));
        return [
            "transcription" => $text,
            'language' => $languageCode,
        ];
    }

    public function mapChunkTypeToString(ChunkType $chunkType): string
    {
        return match ($chunkType) {
            ChunkType::Text => 'text',
            ChunkType::Thinking => 'thinking',
            ChunkType::Meta => 'meta',
        };
    }

    public function  saveAiMessage($chatId, $content)
    {
        $aiMessage = new Message([
            'chat_id' => $chatId,
            'user_id' => null,
            'type' => 'text',
            'content' => $content,
            'is_read' => false,
        ]);

        $aiMessage->save();
        return $aiMessage;
    }
}
