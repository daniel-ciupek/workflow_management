<?php

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
        $path = base64_decode($path);
        abort_unless(Storage::disk('tasks')->exists($path), 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) {
            return response(Storage::disk('tasks')->get($path), 200)
                ->header('Content-Type', 'image/jpeg');
        }
        return Storage::disk('tasks')->download($path, basename($path));
    })->where('path', '.+')->name('attachments.view');
});

// Employee routes — session-based, no auth required
Route::middleware(['isEmployee'])->prefix('employee')->name('employee.')->group(function () {
    Route::view('dashboard', 'employee.dashboard')->name('dashboard');
    Route::view('history', 'employee.task-history')->name('history');

    // Attachment view (inline for images, download for PDF)
    Route::get('attachments/{path}', function (string $path) {
        $path = base64_decode($path);
        abort_unless(Storage::disk('tasks')->exists($path), 404);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) {
            return response(Storage::disk('tasks')->get($path), 200)
                ->header('Content-Type', 'image/jpeg');
        }
        return Storage::disk('tasks')->download($path, basename($path));
    })->where('path', '.+')->name('attachments.view');
});

Route::get('employee-logout', function () {
    session()->forget('employee_access');
    return redirect()->route('login');
})->name('employee.logout');

require __DIR__.'/auth.php';
