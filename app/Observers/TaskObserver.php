<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class TaskObserver
{
    public function deleting(Task $task): void
    {
        if (!empty($task->attachments)) {
            foreach ($task->attachments as $path) {
                Storage::disk('tasks')->delete($path);
            }
            Storage::disk('tasks')->deleteDirectory("task-{$task->id}");
        }
    }

    public static function pruneCompletedForUser(User $user): void
    {
        $completed = $user->tasks()
            ->wherePivot('done', true)
            ->orderByPivot('completed_at', 'asc')
            ->get();

        if ($completed->count() <= 10) {
            return;
        }

        foreach ($completed->slice(0, $completed->count() - 10) as $task) {
            $user->tasks()->detach($task->id);

            if ($task->fresh()->users()->count() === 0) {
                $task->delete();
            }
        }
    }
}
