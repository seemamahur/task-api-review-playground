<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public function test_can_create_and_fetch_task(): void
    {
        $this->postJson('/api/tasks', ['title' => 'write tests'])
            ->assertCreated()
            ->assertJsonPath('data.title', 'write tests');

        $this->getJson('/api/tasks/1')
            ->assertOk()
            ->assertJsonPath('data.title', 'write tests');
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

    public function test_per_page_is_capped(): void
    {
        $this->getJson('/api/tasks?per_page=100000')->assertStatus(422);
    }

    public function test_blank_title_on_update_is_rejected(): void
    {
        $task = Task::create(['title' => 'keep me']);

        $this->patchJson("/api/tasks/{$task->id}", ['title' => ''])->assertStatus(422);
    }

    public function test_wildcard_search_is_escaped(): void
    {
        Task::create(['title' => 'plain']);

        $this->getJson('/api/tasks?q=%25')->assertOk()->assertJsonPath('meta.total', 0);
    }
}
