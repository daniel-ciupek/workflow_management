<!DOCTYPE html>
<html lang="en" data-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base-200 min-h-screen">

    <div x-data="{ open: false }" class="sticky top-0 z-50">
        <div class="navbar bg-base-100 shadow-sm px-4">
            <div class="navbar-start gap-4">
                @if(auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold text-primary hover:opacity-80 transition-opacity">Workflow Management</a>
                @elseif(session('employee_access'))
                    <a href="{{ route('employee.dashboard') }}" class="text-lg font-bold text-primary hover:opacity-80 transition-opacity">Workflow Management</a>
                @else
                    <span class="text-lg font-bold text-primary">Workflow Management</span>
                @endif
                @auth
                    @if(auth()->user()->isAdmin())
                        <nav class="hidden md:flex gap-1">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.dashboard') ? 'btn-active' : '' }}">Dashboard</a>
                            <a href="{{ route('admin.employees') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.employees') ? 'btn-active' : '' }}">Employees</a>
                            <a href="{{ route('admin.tasks') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.tasks') || request()->routeIs('admin.tasks.create') ? 'btn-active' : '' }}">Tasks</a>
                            <a href="{{ route('admin.tasks.history') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.tasks.history') ? 'btn-active' : '' }}">Task History</a>
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.admins') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.admins') ? 'btn-active' : '' }}">Admins</a>
                            @endif
                        </nav>
                    @endif
                @endauth
            </div>

            <div class="navbar-end gap-2">
                @auth
                    <span class="text-sm text-base-content/70 hidden sm:inline">
                        {{ auth()->user()->name }}
                        <span class="badge badge-ghost badge-sm ml-1">{{ ucfirst(auth()->user()->role) }}</span>
                    </span>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.change-pin') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">Settings</a>
                        <button @click="open = !open" class="btn btn-ghost btn-sm md:hidden">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Sign Out</button>
                    </form>
                @else
                    @if(session('employee_access'))
                        <span class="text-sm text-base-content/70 hidden sm:inline">Employee View</span>
                        <a href="{{ route('employee.dashboard') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('employee.dashboard') ? 'btn-active' : '' }}">Tasks</a>
                        <a href="{{ route('employee.history') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('employee.history') ? 'btn-active' : '' }}">History</a>
                        <a href="{{ route('employee.logout') }}" class="btn btn-ghost btn-sm text-error">Sign Out</a>
                    @endif
                @endauth
            </div>
        </div>

        {{-- Mobile menu (admin only) --}}
        @auth
            @if(auth()->user()->isAdmin())
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     @click.outside="open = false"
                     class="md:hidden bg-base-100 border-t border-base-200 shadow-md px-4 py-3 flex flex-col gap-1"
                     style="display: none;">
                    <a href="{{ route('admin.dashboard') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.dashboard') ? 'btn-active' : '' }}">Dashboard</a>
                    <a href="{{ route('admin.employees') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.employees') ? 'btn-active' : '' }}">Employees</a>
                    <a href="{{ route('admin.tasks') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.tasks') || request()->routeIs('admin.tasks.create') ? 'btn-active' : '' }}">Tasks</a>
                    <a href="{{ route('admin.tasks.history') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.tasks.history') ? 'btn-active' : '' }}">Task History</a>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.admins') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.admins') ? 'btn-active' : '' }}">Admins</a>
                    @endif
                    <div class="divider my-1"></div>
                    <a href="{{ route('admin.change-pin') }}" @click="open = false" class="btn btn-ghost btn-sm justify-start {{ request()->routeIs('admin.change-pin') ? 'btn-active' : '' }}">Settings</a>
                </div>
            @endif
        @endauth
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

</body>
</html>
