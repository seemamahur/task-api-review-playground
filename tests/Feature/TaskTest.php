<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class TaskTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }
    public function test_can_create_and_fetch_task(): void
    {
        $this->postJson('/api/tasks', ['title' => 'write tests'])
            ->assertCreated()
            ->assertJsonPath('title', 'write tests');

        $this->getJson('/api/tasks/1')->assertOk()->assertJsonPath('title', 'write tests');
    }

    public function test_title_is_required(): void
    {
        $this->postJson('/api/tasks', [])->assertStatus(422);
    }

    public function test_missing_task_returns_404(): void
    {
        $this->getJson('/api/tasks/999')->assertNotFound();
    }
    // public function test_example(): void
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }
}
