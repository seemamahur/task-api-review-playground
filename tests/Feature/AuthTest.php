<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }


    use RefreshDatabase;

    public function test_quotes_in_name_and_email_are_stored_safely(): void
    {
        $payload = [
            'name'     => "Robert'); DROP TABLE users;--",
            'email'    => "o'brien@example.com",
            'password' => 'secret123',
        ];

        $this->postJson('/api/register', $payload)->assertCreated();
        $this->assertDatabaseHas('users', ['name' => $payload['name'], 'email' => $payload['email']]);

        $this->postJson('/api/login', ['email' => $payload['email'], 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_tasks_require_authentication(): void
    {
        $this->getJson('/api/tasks')->assertUnauthorized();
    }

    public function test_login_token_has_expiry(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Tester',
            'email' => 't@example.com',
            'password' => 'secret123',
        ]);
        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'secret123']);

        $this->assertNotNull(PersonalAccessToken::first()->expires_at);
    }

    public function test_register_and_login(): void
    {
        $payload = ['name' => 'Tester', 'email' => 't@example.com', 'password' => 'secret123'];

        $this->postJson('/api/register', $payload)->assertCreated();
        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_rejects_bad_password(): void
    {
        User::factory()->create(['email' => 't@example.com']);

        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(401);
    }

    public function test_sql_injection_attempt_fails(): void
    {
        $this->postJson('/api/login', ['email' => "' OR '1'='1", 'password' => 'x'])
            ->assertStatus(422);
    }
}
