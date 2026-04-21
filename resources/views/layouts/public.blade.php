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
<body class="min-h-screen flex flex-col">
    @include('layouts.partials.gov-strip')
    @php
        $publicNav = '
            <nav class="flex space-x-6 text-sm">
                <a href="' . url('/') . '" class="text-ink-700 hover:text-senegal-green font-medium">Accueil</a>
                <a href="' . route('demandes.create') . '" class="text-ink-700 hover:text-senegal-green font-medium">Faire une demande</a>
                <a href="' . url('/#verification') . '" class="text-ink-700 hover:text-senegal-green font-medium">Vérifier un acte</a>
                <a href="' . route('login') . '" class="text-ink-700 hover:text-senegal-green font-medium">Connexion</a>
            </nav>
        ';
    @endphp
    @include('layouts.partials.institutional-header', ['nav' => $publicNav])

    <main class="flex-1">
        @hasSection('content')
            @yield('content')
        @else
            {{ $slot }}
        @endif
    </main>

    @include('layouts.partials.footer')
    @stack('scripts')
</body>
</html>
