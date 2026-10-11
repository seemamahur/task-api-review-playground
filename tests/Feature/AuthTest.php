<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_and_login_returns_a_usable_token(): void
    {
        $payload = ['name' => 'Tester', 'email' => 't@example.com', 'password' => 'secret123'];

        $this->postJson('/api/register', $payload)->assertCreated();

        $token = $this->postJson('/api/login', [
            'email' => 't@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token'])->json('token');

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', 't@example.com');
    }

    public function test_login_rejects_bad_password(): void
    {
        User::factory()->create(['email' => 't@example.com']);

        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(401);
    }

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

    public function test_token_expires_in_about_24_hours(): void
    {
        User::factory()->create(['email' => 't@example.com', 'password' => 'secret123']);

        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'secret123'])
            ->assertOk();

        $token = PersonalAccessToken::firstOrFail();
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->between(now()->addHours(23), now()->addHours(25)));
    }

    public function test_second_login_keeps_other_device_sessions(): void
    {
        User::factory()->create(['email' => 't@example.com', 'password' => 'secret123']);
        $creds = ['email' => 't@example.com', 'password' => 'secret123'];

        $this->postJson('/api/login', $creds)->assertOk();
        $this->postJson('/api/login', $creds)->assertOk();

        $this->assertSame(2, PersonalAccessToken::count());
    }

    public function test_expired_tokens_are_removed_on_login(): void
    {
        $user = User::factory()->create(['email' => 't@example.com', 'password' => 'secret123']);
        $user->createToken('api', ['*'], now()->subDay());

        $this->postJson('/api/login', ['email' => 't@example.com', 'password' => 'secret123'])
            ->assertOk();

        $this->assertSame(1, PersonalAccessToken::count());
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 't@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Dup',
            'email' => 't@example.com',
            'password' => 'secret123',
        ])->assertStatus(422);
    }

    public function test_register_validates_input(): void
    {
        $this->postJson('/api/register', ['name' => 'X', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited(): void
    {
        $creds = ['email' => 'nobody@example.com', 'password' => 'whatever1'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', $creds)->assertStatus(401);
        }

        $this->postJson('/api/login', $creds)->assertStatus(429);
    }
}
