<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Teacher Portal' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>

    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/mask@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full">
    <div class="flex min-h-screen">
        <aside class="w-64 bg-indigo-900 text-white hidden md:flex flex-col fixed h-full shadow-xl">
            <div class="p-6 border-b border-indigo-800/50">
                <a href="/dashboard" class="flex items-center gap-3 hover:opacity-90 transition-opacity">
                    <div class="bg-indigo-500 p-2 rounded-lg shadow-inner">
                        <x-heroicon-s-academic-cap class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-xl font-semibold tracking-tight text-white uppercase group-hover:text-indigo-200 transition-colors">
                        Edu<span class="font-extrabold text-indigo-400">Cen</span>
                    </span>
                </a>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard*')" icon="home">Dashboard</x-nav-link>

                @can('manage teachers')
                    <x-nav-link :href="route('teachers')" :active="request()->routeIs('teachers*')" icon="user">Teachers</x-nav-link>
                @endcan

                @can('manage students')
                    <x-nav-link :href="route('students.index')" :active="request()->routeIs('students*')" icon="users">Students</x-nav-link>
                @endcan

                @can('edit scores')
                    <x-nav-link :href="route('scores.index')" :active="request()->routeIs('scores*')" icon="academic-cap">Scores</x-nav-link>
                @endcan

                @can('manage grades')
                    <x-nav-link :href="route('grades')" :active="request()->routeIs('grades*')" icon="numbered-list">Grades</x-nav-link>
                @endcan
            </nav>
            <div class="p-4 border-t border-indigo-800">
                <x-nav-link :href="route('admin')" :active="request()->routeIs('admin*')" icon="adjustments-horizontal">Administration</x-nav-link>
                <span class="text-sm opacity-75">{{ auth()->user()->name }}</span>
            </div>
        </aside>

        <div class="flex-1 flex flex-col md:ml-64">
            <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8">
                <h1 class="text-xl font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h1>
                <div class="flex items-center gap-4">
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-sm text-gray-600 hover:text-red-600">Logout</button>
                    </form>
                </div>
            </header>

            <main class="p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    
    @fluxScripts
</body>
</html>