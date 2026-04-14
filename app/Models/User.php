<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'pin',
        'role',
        'is_super',
    ];

    protected $hidden = [
        'pin',
    ];

    protected function casts(): array
    {
        return [
            'role'     => 'string',
            'is_super' => 'boolean',
        ];
    }

    public function setPinAttribute(?string $value): void
    {
        $this->attributes['pin'] = $value !== null ? Hash::make($value) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' && (bool) $this->is_super;
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    // Employee → their admins
    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_employee', 'employee_id', 'admin_id');
    }

    // Admin → their employees
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_employee', 'admin_id', 'employee_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withPivot('done', 'completed_at');
    }
}
