<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

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
