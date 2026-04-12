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
<body class="font-sans antialiased min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #EFF6FF 0%, #F1F5F9 50%, #E0E7FF 100%);">
    <div class="w-full max-w-sm px-4">
        {{ $slot }}
    </div>
</body>
</html>
