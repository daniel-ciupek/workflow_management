<?php

namespace Tests\Feature\Commands;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveOldTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_older_than_24h_are_archived(): void
    {
        $oldTask = Task::factory()->create(['created_at' => now()->subHours(25)]);

        $this->artisan('app:archive-old-tasks')->assertSuccessful();

        $this->assertNotNull($oldTask->fresh()->archived_at);
    }

    public function test_tasks_younger_than_24h_are_not_archived(): void
    {
        $newTask = Task::factory()->create(['created_at' => now()->subHours(23)]);

        $this->artisan('app:archive-old-tasks')->assertSuccessful();

        $this->assertNull($newTask->fresh()->archived_at);
    }

    public function test_already_archived_tasks_are_not_touched(): void
    {
        $archivedAt = now()->subDays(3);
        $task = Task::factory()->create([
            'created_at'  => now()->subHours(25),
            'archived_at' => $archivedAt,
        ]);

        $this->artisan('app:archive-old-tasks')->assertSuccessful();

        // archived_at should not be overwritten
        $this->assertEquals(
            $archivedAt->toDateTimeString(),
            $task->fresh()->archived_at->toDateTimeString()
        );
    }

    public function test_command_reports_correct_archived_count(): void
    {
        Task::factory()->create(['created_at' => now()->subHours(25)]);
        Task::factory()->create(['created_at' => now()->subHours(26)]);
        Task::factory()->create(['created_at' => now()->subHours(23)]); // should not archive

        $this->artisan('app:archive-old-tasks')
            ->expectsOutput('Archived 2 task(s).')
            ->assertSuccessful();
    }
}
