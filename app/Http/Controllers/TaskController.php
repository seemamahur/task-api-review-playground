<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Http\JsonResponse;


class TaskController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->tasks()->orderBy('id')->get();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        return response()->json($request->user()->tasks()->create($data), 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->ensureOwner($request, $task);

        return $task;
    }

    public function update(Request $request, Task $task)
    {
        $this->ensureOwner($request, $task);

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'done'  => 'sometimes|boolean',
        ]);

        $task->update($data);

        return $task;
    }

    public function destroy(Request $request, Task $task)
    {
        $this->ensureOwner($request, $task);
        $task->delete();

        return response()->noContent();
    }

    // 404 (not 403) so other users can't tell whether a task id exists.
    private function ensureOwner(Request $request, Task $task): void
    {
        abort_if($task->user_id !== $request->user()->id, 404);
    }
}
