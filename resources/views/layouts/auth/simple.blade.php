<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
        <!-- Ensure Outfit font is loaded if not already in partials.head -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com" rel="stylesheet">
        
        <style>
            body { font-family: 'Outfit', sans-serif; }
        </style>
    </head>
    <body class="h-full bg-gray-50 antialiased dark:bg-zinc-950">
        <div class="flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-6">
                <!-- Branding: Matches Sidebar Logo -->
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 group" wire:navigate>
                    <div class="bg-indigo-900 p-3 rounded-2xl shadow-xl transition-transform group-hover:scale-105">
                        <x-heroicon-s-academic-cap class="w-10 h-10 text-white" />
                    </div>
                    
                    <div class="text-2xl font-semibold tracking-tight text-gray-900 uppercase dark:text-white">
                        Edu<span class="font-extrabold text-indigo-600">Cen</span>
                    </div>
                </a>

                <!-- Auth Form Card -->
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
