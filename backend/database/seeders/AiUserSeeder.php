<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AiUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create(attributes: [
            'name' => 'AI Assistant',
            'email' => 'ai@assistant.com',
            'password' => Hash::make('ai-password-' . time()),
        ]);
    }
}
