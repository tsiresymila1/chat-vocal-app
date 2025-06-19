<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_get_their_chats()
    {
        // Create some chats for the user
        $chats = Chat::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_active' => true
        ]);

        // Create a chat for another user
        Chat::factory()->create([
            'is_active' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/chats');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'title',
                    'user_id',
                    'is_active',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'id',
                        'name',
                        'email'
                    ],
                    'messages'
                ]
            ]);
    }

    public function test_user_can_create_a_chat()
    {
        $chatData = [
            'title' => 'Test Chat'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/chats', $chatData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'title',
                'user_id',
                'is_active',
                'created_at',
                'updated_at',
                'user' => [
                    'id',
                    'name',
                    'email'
                ]
            ]);

        $this->assertDatabaseHas('chats', [
            'title' => 'Test Chat',
            'user_id' => $this->user->id,
            'is_active' => true
        ]);
    }

    public function test_user_can_view_their_chat()
    {
        $chat = Chat::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chats/{$chat->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'user_id',
                'is_active',
                'created_at',
                'updated_at',
                'user' => [
                    'id',
                    'name',
                    'email'
                ],
                'messages'
            ]);
    }

    public function test_user_cannot_view_another_users_chat()
    {
        $otherUser = User::factory()->create();
        $chat = Chat::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/chats/{$chat->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_chat()
    {
        $chat = Chat::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/chats/{$chat->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('chats', ['id' => $chat->id]);
    }

    public function test_user_cannot_delete_another_users_chat()
    {
        $otherUser = User::factory()->create();
        $chat = Chat::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/chats/{$chat->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('chats', ['id' => $chat->id]);
    }
} 