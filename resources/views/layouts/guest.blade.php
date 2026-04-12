<!DOCTYPE html>
<html lang="en" data-theme="workflow">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center relative overflow-hidden" style="background: linear-gradient(150deg, #EFF6FF 0%, #F1F5F9 45%, #E0E7FF 100%);">
    {{-- Decorative blobs --}}
    <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-blue-100 rounded-full opacity-40 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-[-8%] right-[-4%] w-80 h-80 bg-indigo-100 rounded-full opacity-35 blur-3xl pointer-events-none"></div>
    <div class="absolute top-[55%] left-[60%] w-56 h-56 bg-sky-100 rounded-full opacity-30 blur-2xl pointer-events-none"></div>

    <div class="w-full max-w-sm px-4 relative z-10">
        {{ $slot }}
    </div>
</body>
</html>
