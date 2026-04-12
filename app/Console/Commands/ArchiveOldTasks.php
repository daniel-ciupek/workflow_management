<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ArchiveOldTasks extends Command
{
    protected $signature = 'app:archive-old-tasks';
    protected $description = 'Archive tasks older than 24 hours and keep only the last 5 archived per employee';

    private const MAX_ARCHIVED_PER_EMPLOYEE = 5;

    public function handle(): void
    {
        $archived = Task::whereNull('archived_at')
            ->where('created_at', '<=', now()->subHours(24))
            ->update(['archived_at' => now()]);

        $this->info("Archived {$archived} task(s).");

        // For each employee, keep only the last MAX_ARCHIVED_PER_EMPLOYEE archived tasks
        $deleted = 0;
        $employeeIds = \App\Models\User::where('role', 'employee')->pluck('id');

        foreach ($employeeIds as $employeeId) {
            $keepIds = Task::archived()
                ->whereHas('users', fn ($q) => $q->where('users.id', $employeeId))
                ->latest('archived_at')
                ->limit(self::MAX_ARCHIVED_PER_EMPLOYEE)
                ->pluck('id');

            $toDelete = Task::archived()
                ->whereHas('users', fn ($q) => $q->where('users.id', $employeeId))
                ->whereNotIn('id', $keepIds)
                ->get();

            foreach ($toDelete as $task) {
                // Delete attachments from storage
                if (!empty($task->attachments)) {
                    foreach ($task->attachments as $path) {
                        Storage::disk('tasks')->delete($path);
                    }
                }
                $task->delete();
                $deleted++;
            }
        }

        $this->info("Pruned {$deleted} old archived task(s).");
    }
}
