<?php

use App\Models\Task;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', fn () => redirect()->route('login'));

// Admin routes
Route::middleware(['isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('change-pin', 'admin.change-pin')->name('change-pin');

    // Employees
    Route::view('employees', 'admin.employees')->name('employees');

    // Tasks
    Route::view('tasks', 'admin.tasks')->name('tasks');
    Route::view('tasks/create', 'admin.tasks-create')->name('tasks.create');
    Route::view('tasks/history', 'admin.task-history')->name('tasks.history');

    // Admins management
    Route::view('admins', 'admin.admins')->name('admins');

    // Attachment view (inline for images, download for PDF)
    Route::get('attachments/{path}', function (string $path) {
        $decoded = base64_decode($path, true);
        // Strict format: task-{integer}/{filename} — prevents path traversal
        abort_unless($decoded && preg_match('/^task-(\d+)\/[^\/\\\\]+$/', $decoded, $m), 403);
        $taskId = (int) $m[1];

        // Authorize: regular admin must own the task; super admin can access any
        $admin = auth()->user();
        if (!$admin->isSuperAdmin()) {
            abort_unless(Task::where('id', $taskId)->where('created_by', $admin->id)->exists(), 403);
        }

        abort_unless(Storage::disk('tasks')->exists($decoded), 404);
        $ext = strtolower(pathinfo($decoded, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) {
            return response(Storage::disk('tasks')->get($decoded), 200)
                ->header('Content-Type', 'image/jpeg');
        }
        return Storage::disk('tasks')->download($decoded, basename($decoded));
    })->where('path', '.+')->name('attachments.view');
});

// Employee routes — session-based, no auth required
Route::middleware(['isEmployee'])->prefix('employee')->name('employee.')->group(function () {
    Route::view('select', 'employee.select')->name('select');
    Route::view('dashboard', 'employee.dashboard')->name('dashboard');
    Route::view('history', 'employee.task-history')->name('history');

    // Attachment view (inline for images, download for PDF)
    Route::get('attachments/{path}', function (string $path) {
        $decoded = base64_decode($path, true);
        // Strict format: task-{integer}/{filename} — prevents path traversal
        abort_unless($decoded && preg_match('/^task-(\d+)\/[^\/\\\\]+$/', $decoded, $m), 403);
        $taskId = (int) $m[1];

        // Authorize: employee must be assigned to this task
        $employeeId = session('employee_id');
        abort_unless(
            Task::whereHas('users', fn ($q) => $q->where('users.id', $employeeId))
                ->where('id', $taskId)
                ->exists(),
            403
        );

        abort_unless(Storage::disk('tasks')->exists($decoded), 404);
        $ext = strtolower(pathinfo($decoded, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) {
            return response(Storage::disk('tasks')->get($decoded), 200)
                ->header('Content-Type', 'image/jpeg');
        }
        return Storage::disk('tasks')->download($decoded, basename($decoded));
    })->where('path', '.+')->name('attachments.view');
});

Route::get('employee-logout', function () {
    session()->forget(['employee_access', 'employee_id', 'employee_name']);
    return redirect()->route('login');
})->name('employee.logout');

require __DIR__.'/auth.php';
