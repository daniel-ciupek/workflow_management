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

    <div class="navbar bg-base-100 shadow-sm px-4 sticky top-0 z-50">
        <div class="navbar-start gap-4">
            <span class="text-lg font-bold text-primary">Workflow Management</span>
            @auth
                @if(auth()->user()->isAdmin())
                    <nav class="hidden md:flex gap-1">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.dashboard') ? 'btn-active' : '' }}">Dashboard</a>
                        <a href="{{ route('admin.employees') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.employees') ? 'btn-active' : '' }}">Employees</a>
                        <a href="{{ route('admin.tasks') }}" class="btn btn-ghost btn-sm {{ request()->routeIs('admin.tasks*') ? 'btn-active' : '' }}">Tasks</a>
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
                    <a href="{{ route('admin.change-pin') }}" class="btn btn-ghost btn-sm">Settings</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm text-error">Sign Out</button>
                </form>
            @else
                @if(session('employee_access'))
                    <span class="text-sm text-base-content/70 hidden sm:inline">Employee View</span>
                    <a href="{{ route('employee.logout') }}" class="btn btn-ghost btn-sm text-error">Sign Out</a>
                @endif
            @endauth
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

</body>
</html>
