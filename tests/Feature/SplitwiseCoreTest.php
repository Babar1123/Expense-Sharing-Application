<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Tests\TestCase;

class SplitwiseCoreTest extends TestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::query()->delete();
        Group::query()->delete();
        Token::query()->delete();
    }

    public function test_user_can_register_and_receive_token(): void
    {
        $payload = [
            'name' => 'Ali',
            'email' => 'ali@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['name', 'email'], 'token'],
            ]);

        $this->assertTrue(User::where('email', 'ali@example.com')->exists());
        $this->assertTrue(Token::where('user_id', User::first()->_id)->exists());
    }

    public function test_user_can_login_and_get_token(): void
    {
        User::create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
            'password' => bcrypt('Password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'ahmed@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user' => ['name', 'email'], 'token'],
            ]);
    }

    public function test_authenticated_user_can_create_group(): void
    {
        $user = User::create([
            'name' => 'Usman',
            'email' => 'usman@example.com',
            'password' => bcrypt('Password123'),
        ]);

        $token = Token::create([
            'user_id' => $user->_id,
            'token' => 'test-token-123',
            'revoked_at' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token->token)
            ->postJson('/api/groups', [
                'name' => 'Dubai Trip',
                'description' => 'Trip expenses',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Dubai Trip');

        $this->assertTrue(Group::where('name', 'Dubai Trip')->where('owner_id', $user->_id)->exists());
    }
}
