<?php

namespace App\Console\Commands;

use App\Models\Task;
use Illuminate\Console\Command;

class ArchiveOldTasks extends Command
{
    protected $signature = 'app:archive-old-tasks';
    protected $description = 'Archive tasks older than 48 hours';

    public function handle(): void
    {
        $count = Task::whereNull('archived_at')
            ->where('created_at', '<=', now()->subHours(48))
            ->update(['archived_at' => now()]);

        $this->info("Archived {$count} task(s).");
    }
}
