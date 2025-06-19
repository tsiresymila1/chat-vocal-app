<?php

namespace Tests\Unit;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\AiChatService;
use App\Services\PrismClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Enums\ChunkType;
use Tests\TestCase;

class AiChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiChatService $aiChatService;
    private Chat $chat;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->chat = Chat::factory()->create([
            'user_id' => $this->user->id
        ]);

        $fakePrismClient = \Mockery::mock(PrismClient::class);
        $fakePrismClient->shouldReceive('getAiResponse')
            ->andReturn(['content' => 'AI response']);
        $fakePrismClient->shouldReceive('getStreamResponse')
            ->andReturn([
                (object)['chunkType' => 'text', 'text' => 'Hello'],
                (object)['chunkType' => 'text', 'text' => ' World']
            ]);

        $this->aiChatService = new AiChatService($fakePrismClient);
    }

    public function test_generate_response_creates_ai_message()
    {
        // Create some previous messages
        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'content' => 'Hello AI'
        ]);

        Message::factory()->create([
            'chat_id' => $this->chat->id,
            'user_id' => null,
            'content' => 'Hi there!'
        ]);

        $message = Message::factory()->create([
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'content' => 'How are you?'
        ]);

        $response = $this->aiChatService->generateResponse($this->chat, $message);

        $this->assertInstanceOf(Message::class, $response);
        $this->assertEquals($this->chat->id, $response->chat_id);
        $this->assertNull($response->user_id);
        $this->assertEquals('text', $response->type);
        $this->assertFalse($response->is_read);
    }

    public function test_transcribe_audio()
    {
       Http::fake([
            'https://api.openai.com/v1/audio/transcriptions' => Http::response('Transcribed text', 200, [
                'Content-Type' => 'text/plain'
            ])
        ]);
        $audioPath = storage_path('app/public/test.mp3');
        file_put_contents($audioPath, 'test audio content'); 
        $result = $this->aiChatService->transcribeAudio($audioPath);
        $this->assertEquals('Transcribed text', $result);
        unlink($audioPath);
    }

    public function test_transcribe_audio_handles_failure()
    {
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response('Error', 500)
        ]);

        $audioPath = storage_path('app/public/test.mp3');
        file_put_contents($audioPath, 'test audio content');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transcription failed: Error');

        $this->aiChatService->transcribeAudio($audioPath);

        unlink($audioPath);
    }

    public function test_map_chunk_type_to_string()
    {
        $this->assertEquals('text', $this->aiChatService->mapChunkTypeToString(ChunkType::Text));
        $this->assertEquals('thinking', $this->aiChatService->mapChunkTypeToString(ChunkType::Thinking));
        $this->assertEquals('meta', $this->aiChatService->mapChunkTypeToString(ChunkType::Meta));
    }

    public function test_save_ai_message()
    {
        $content = 'AI response message';
        $message = $this->aiChatService->saveAiMessage($this->chat->id, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($this->chat->id, $message->chat_id);
        $this->assertNull($message->user_id);
        $this->assertEquals('text', $message->type);
        $this->assertEquals($content, $message->content);
        $this->assertFalse($message->is_read);
    }
} 