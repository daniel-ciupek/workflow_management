<!DOCTYPE html>
<html lang="en" data-theme="workflow">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-base-200 min-h-screen">

    <div x-data="{ open: false }" class="sticky top-0 z-50">
        <div class="bg-white border-b border-slate-200 px-4 lg:px-6" style="box-shadow: 0 1px 3px 0 rgba(0,0,0,0.06);">
            <div class="flex items-center justify-between h-14 max-w-7xl mx-auto">

                {{-- Logo --}}
                <div class="flex-shrink-0">
                    @if(auth()->check() && auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 group">
                            <div class="w-7 h-7 bg-primary rounded-lg flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 hidden sm:block">Workflow</span>
                        </a>
                    @elseif(session('employee_access'))
                        <a href="{{ route('employee.dashboard') }}" class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-primary rounded-lg flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 hidden sm:block">Workflow</span>
                        </a>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 bg-primary rounded-lg flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 hidden sm:block">Workflow</span>
                        </div>
                    @endif
                </div>

                {{-- Desktop navigation (admin) --}}
                @auth
                    @if(auth()->user()->isAdmin())
                        <nav class="hidden md:flex items-center h-14">
                            <a href="{{ route('admin.dashboard') }}"
                               class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('admin.dashboard') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.employees') }}"
                               class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('admin.employees') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                                Employees
                            </a>
                            <a href="{{ route('admin.tasks') }}"
                               class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('admin.tasks') || request()->routeIs('admin.tasks.create') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                                Tasks
                            </a>
                            <a href="{{ route('admin.tasks.history') }}"
                               class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('admin.tasks.history') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                                History
                            </a>
                            @if(auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.admins') }}"
                                   class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('admin.admins') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                                    Admins
                                </a>
                            @endif
                        </nav>
                    @endif
                @endauth

                {{-- Desktop navigation (employee) --}}
                @if(!auth()->check() && session('employee_access'))
                    <nav class="hidden md:flex items-center h-14">
                        <a href="{{ route('employee.dashboard') }}"
                           class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('employee.dashboard') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                            Tasks
                        </a>
                        <a href="{{ route('employee.history') }}"
                           class="px-3.5 text-sm font-medium h-full flex items-center border-b-2 transition-colors duration-150 {{ request()->routeIs('employee.history') ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-900 hover:border-slate-300' }}">
                            History
                        </a>
                    </nav>
                @endif

                {{-- Right side --}}
                <div class="flex items-center gap-2">
                    @auth
                        <div class="hidden sm:flex items-center gap-2 mr-1">
                            <span class="text-sm text-slate-700 font-medium">{{ auth()->user()->name }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
                                {{ ucfirst(auth()->user()->role) }}
                            </span>
                        </div>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.change-pin') }}"
                               class="hidden sm:inline-flex items-center px-3 py-1.5 text-sm font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-md transition-colors duration-150">
                                Settings
                            </a>
                            <button @click="open = !open"
                                    class="md:hidden p-2 rounded-md text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors duration-150">
                                <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                <svg x-show="open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-500 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors duration-150">
                                Sign Out
                            </button>
                        </form>
                    @else
                        @if(session('employee_access'))
                            @if(session('employee_name'))
                                <div class="hidden sm:flex items-center gap-2 mr-1">
                                    <span class="text-sm text-slate-700 font-medium">{{ session('employee_name') }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">Employee</span>
                                </div>
                            @endif
                            <button @click="open = !open"
                                    class="md:hidden p-2 rounded-md text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors duration-150">
                                <svg x-show="!open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                                <svg x-show="open" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                            <a href="{{ route('employee.logout') }}"
                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-500 hover:text-red-700 hover:bg-red-50 rounded-md transition-colors duration-150">
                                Sign Out
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        {{-- Mobile menu (employee) --}}
        @if(session('employee_access') && !auth()->check())
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-1"
                 @click.outside="open = false"
                 class="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex flex-col gap-1"
                 style="display: none;">
                <a href="{{ route('employee.dashboard') }}" @click="open = false"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('employee.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    Tasks
                </a>
                <a href="{{ route('employee.history') }}" @click="open = false"
                   class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('employee.history') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                    History
                </a>
            </div>
        @endif

        {{-- Mobile menu (admin) --}}
        @auth
            @if(auth()->user()->isAdmin())
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     @click.outside="open = false"
                     class="md:hidden bg-white border-b border-slate-200 px-4 py-3 flex flex-col gap-1"
                     style="display: none;">
                    <div class="flex items-center gap-2 px-3 py-2 mb-1 border-b border-slate-100 pb-3">
                        <span class="text-sm font-medium text-slate-800">{{ auth()->user()->name }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
                            {{ ucfirst(auth()->user()->role) }}
                        </span>
                    </div>
                    <a href="{{ route('admin.dashboard') }}" @click="open = false"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                        Dashboard
                    </a>
                    <a href="{{ route('admin.employees') }}" @click="open = false"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.employees') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                        Employees
                    </a>
                    <a href="{{ route('admin.tasks') }}" @click="open = false"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.tasks') || request()->routeIs('admin.tasks.create') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                        Tasks
                    </a>
                    <a href="{{ route('admin.tasks.history') }}" @click="open = false"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.tasks.history') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                        History
                    </a>
                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.admins') }}" @click="open = false"
                           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.admins') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                            Admins
                        </a>
                    @endif
                    <div class="border-t border-slate-100 mt-1 pt-1">
                        <a href="{{ route('admin.change-pin') }}" @click="open = false"
                           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-md {{ request()->routeIs('admin.change-pin') ? 'bg-blue-50 text-blue-700' : 'text-slate-700' }}">
                            Settings
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        {{ $slot }}
    </main>

</body>
</html>
