<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index()
    {
        try {
            $tasks = Task::latest()->paginate(10);
            return view('admin.tasks.index', compact('tasks'));
        } catch (\Exception $e) {
            Log::error('Failed to fetch tasks: ' . $e->getMessage());
            return back()->with('error', 'Failed to fetch tasks');
        }
    }

    public function store(Request $request)
    {
        try {
            $result = $this->taskService->createTask(
                $request->name,
                $request->description,
                $request->type,
                $request->priority,
                $request->due_date,
                $request->assigned_to,
                $request->details ?? []
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to create task: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create task'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $result = $this->taskService->updateTask(
                $id,
                $request->name,
                $request->description,
                $request->type,
                $request->priority,
                $request->due_date,
                $request->assigned_to,
                $request->details ?? []
            );
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to update task: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update task'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $result = $this->taskService->deleteTask($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to delete task: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete task'], 500);
        }
    }

    public function updateProgress(Request $request, $id)
    {
        try {
            $result = $this->taskService->updateTaskProgress($id, $request->progress);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to update task progress: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update task progress'], 500);
        }
    }

    public function complete($id)
    {
        try {
            $result = $this->taskService->completeTask($id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to complete task: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to complete task'], 500);
        }
    }
} 