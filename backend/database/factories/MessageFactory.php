<?php

namespace Database\Factories;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'chat_id' => Chat::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'type' => 'text',
            'audio_path' => null,
            'is_read' => false,
        ];
    }

    public function audio(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'audio',
            'audio_path' => 'audio_messages/' . $this->faker->uuid() . '.mp3',
        ]);
    }
} 