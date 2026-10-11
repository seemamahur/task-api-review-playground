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
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }
    public function test_user_cannot_access_another_users_task(): void
    {
        $task = User::factory()->create()->tasks()->create(['title' => 'secret']);

        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();
        $this->patchJson("/api/tasks/{$task->id}", ['title' => 'hacked'])->assertNotFound();
        $this->deleteJson("/api/tasks/{$task->id}")->assertNotFound();
        $this->getJson('/api/tasks')->assertOk()->assertJsonCount(0);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'secret']);
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
