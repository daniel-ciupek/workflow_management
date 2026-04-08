<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Admin routes
Route::middleware(['auth', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('change-pin', 'admin.change-pin')->name('change-pin');

    // Employees
    Route::view('employees', 'admin.employees')->name('employees');

    // Tasks
    Route::view('tasks', 'admin.tasks')->name('tasks');
    Route::view('tasks/create', 'admin.tasks-create')->name('tasks.create');
});

// Employee routes
Route::middleware(['auth', 'isEmployee'])->prefix('employee')->name('employee.')->group(function () {
    Route::view('dashboard', 'employee.dashboard')->name('dashboard');
});

require __DIR__.'/auth.php';
