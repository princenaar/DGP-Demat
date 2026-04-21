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
        @php
            $guestNav = '
                <nav class="flex space-x-6 text-sm">
                    <a href="' . url('/') . '" class="text-ink-700 hover:text-senegal-green font-medium">Accueil</a>
                    <a href="' . route('demandes.create') . '" class="text-ink-700 hover:text-senegal-green font-medium">Faire une demande</a>
                    <a href="' . url('/#verification') . '" class="text-ink-700 hover:text-senegal-green font-medium">Vérifier un acte</a>
                </nav>
            ';
        @endphp
        @include('layouts.partials.institutional-header', ['nav' => $guestNav])

        <div class="flex-1 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-paper">
            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-lg overflow-hidden sm:rounded-lg border border-gray-200">
                {{ $slot }}
            </div>
        </div>

        @include('layouts.partials.footer')
        @stack('scripts')
    </body>
</html>
