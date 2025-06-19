<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\AiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Chat $chat;
    private MockInterface $aiChatService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->chat = Chat::factory()->create([
            'user_id' => $this->user->id
        ]);

        $this->aiChatService = Mockery::mock(AiChatService::class);
        $this->app->instance(AiChatService::class, $this->aiChatService);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_user_can_get_messages_from_their_chat()
    {
        // Create some messages
        Message::factory()->count(3)->create([
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chats/{$this->chat->id}/messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'type',
                        'is_read',
                        'audio_path',
                        'created_at',
                        'updated_at',
                        'user' => [
                            'id',
                            'name',
                            'email'
                        ]
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);
    }

    public function test_user_cannot_get_messages_from_another_users_chat()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chats/{$otherChat->id}/messages");

        $response->assertStatus(403);
    }

    public function test_user_can_send_text_message()
    {
        $this->aiChatService
            ->shouldReceive('generateResponse')
            ->once()
            ->andReturn(Message::factory()->create([
                'chat_id' => $this->chat->id,
                'user_id' => null,
                'type' => 'text'
            ]));

        $messageData = [
            'content' => 'Hello, AI!',
            'type' => 'text'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/chats/{$this->chat->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message' => [
                    'id',
                    'content',
                    'type',
                    'is_read',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ]
                ],
                'ai_response' => [
                    'id',
                    'content',
                    'type',
                    'is_read',
                    'created_at',
                    'updated_at',
                    'user'
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'chat_id' => $this->chat->id,
            'user_id' => $this->user->id,
            'content' => 'Hello, AI!',
            'type' => 'text'
        ]);
    }

    public function test_user_can_send_audio_message()
    {
        Storage::fake('public');
        $this->aiChatService
            ->shouldReceive('transcribeAudio')
            ->once()
            ->andReturn('Transcribed text from audio');

        $audioFile = UploadedFile::fake()->create('audio.mp3', 1000);

        $messageData = [
            'content' => 'Audio message',
            'type' => 'audio',
            'audio' => $audioFile
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/chats/{$this->chat->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'content',
                'type',
                'is_read',
                'audio_path',
                'created_at',
                'updated_at',
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);

        $this->assertTrue(Storage::disk('public')->exists('audio_messages/' . $audioFile->hashName()));
    }

    public function test_user_cannot_send_message_to_another_users_chat()
    {
        $otherUser = User::factory()->create();
        $otherChat = Chat::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $messageData = [
            'content' => 'Hello, AI!',
            'type' => 'text'
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/chats/{$otherChat->id}/messages", $messageData);

        $response->assertStatus(403);
    }

    // public function test_streaming_ai_response()
    // {
    //     $chunks = [
    //         (object)['chunkType' => 'text', 'text' => 'Hello'],
    //         (object)['chunkType' => 'text', 'text' => ' World']
    //     ];

    //     $this->aiChatService
    //         ->shouldReceive('generateResponse')
    //         ->once()
    //         ->with(Mockery::type(Chat::class), Mockery::type(Message::class), true)
    //         ->andReturn($chunks);

    //     $this->aiChatService
    //         ->shouldReceive('mapChunkTypeToString')
    //         ->withAnyArgs()
    //         ->times(2)
    //         ->andReturn('text');

    //     $this->aiChatService
    //         ->shouldReceive('saveAiMessage')
    //         ->once()
    //         ->with($this->chat->id, 'Hello World');

    //     $messageData = [
    //         'content' => 'Hello, AI!',
    //         'type' => 'text',
    //         'stream' => true
    //     ];

    //     $response = $this->actingAs($this->user)
    //         ->postJson("/api/chats/{$this->chat->id}/messages", $messageData);

    //     $response->assertStatus(200)
    //         ->assertHeader(headerName: 'Content-Type', 'text/event-stream; charset=UTF-8');
    //     // Consume the streamed response to trigger the generator
    //     $callback = $response->baseResponse->getCallback();
    //     ob_start();
    //     $callback();
    //     ob_end_clean();
    // }
}
