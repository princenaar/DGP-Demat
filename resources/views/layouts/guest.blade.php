<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Portail DRH') }} — MSHP</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased min-h-screen flex flex-col">
        @include('layouts.partials.gov-strip')
        @include('layouts.partials.institutional-header')

        <div class="flex-1 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-paper">
            <div class="w-full sm:max-w-md px-6 mt-6">
                <a href="{{ url('/') }}" class="inline-flex items-center text-sm text-ink-700 hover:text-senegal-green font-medium transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour à l'accueil
                </a>
            </div>
            <div class="w-full sm:max-w-md mt-3 px-6 py-8 bg-white shadow-lg overflow-hidden sm:rounded-lg border border-gray-200">
                {{ $slot }}
            </div>
        </div>

        @include('layouts.partials.footer')
        @stack('scripts')
    </body>
</html>
