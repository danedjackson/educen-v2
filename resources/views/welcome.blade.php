<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to EduCen</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Match your App Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com" rel="stylesheet">

    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="h-full antialiased selection:bg-indigo-500 selection:text-white">
    <div class="relative min-h-screen flex flex-col items-center justify-center overflow-hidden bg-gray-50">
        
        <!-- Top Navigation -->
        <nav class="absolute top-0 w-full p-6 flex justify-end gap-4">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-gray-600 hover:text-indigo-600 transition">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="mt-2 text-sm font-semibold text-gray-600 hover:text-indigo-600 transition">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="text-sm font-semibold text-white bg-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-700 shadow-md transition">Register</a>
                    @endif
                @endauth
            @endif
        </nav>

        <!-- Hero Content -->
        <div class="max-w-3xl px-8 text-center">
            <!-- Brand Logo (Matches Sidebar) -->
            <div class="inline-flex items-center gap-3 mb-8">
                <div class="bg-indigo-900 p-3 rounded-2xl shadow-xl">
                    <x-heroicon-s-academic-cap class="w-10 h-10 text-white" />
                </div>
                <span class="text-4xl font-semibold tracking-tight text-gray-900 uppercase">
                    Edu<span class="font-extrabold text-indigo-600">Cen</span>
                </span>
            </div>

            <h1 class="text-5xl font-bold tracking-tight text-gray-900 sm:text-6xl mb-6">
                Streamline your <span class="text-indigo-600">Student Scoring</span>
            </h1>
            
            <p class="text-lg leading-8 text-gray-600 mb-10">
                A modern platform for teachers to manage grades, track student progress, and organize classroom data with ease and precision.
            </p>

            <div class="flex items-center justify-center gap-x-6">
                <a href="{{ route('login') }}" class="rounded-xl bg-indigo-900 px-8 py-4 text-lg font-semibold text-white shadow-xl hover:bg-indigo-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-all">
                    Get Started
                </a>
                <a href="#" class="text-sm font-semibold leading-6 text-gray-900">Learn more <span aria-hidden="true">→</span></a>
            </div>
        </div>

        <!-- Footer -->
        <footer class="absolute bottom-8 text-sm text-gray-400">
            &copy; {{ date('Y') }} EduCen Portal. All rights reserved.
        </footer>
    </div>
</body>
</html>
