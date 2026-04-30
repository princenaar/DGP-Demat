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
        $navBase = 'border-b-2 border-transparent pb-1 text-ink-700 hover:text-senegal-green font-medium transition-colors';
        $navActive = 'border-b-2 border-senegal-green pb-1 text-ink-900 font-medium';
        $isHome = request()->path() === '/';
        $isCreate = request()->routeIs('demandes.create');
        $isLogin = request()->routeIs('login');
        $publicNav = '
            <nav class="flex space-x-6 text-base">
                <a href="' . url('/') . '" class="' . ($isHome ? $navActive : $navBase) . '">Accueil</a>
                <a href="' . route('demandes.create') . '" class="' . ($isCreate ? $navActive : $navBase) . '">Faire une demande</a>
                <a href="' . url('/#verification') . '" class="' . $navBase . '">Vérifier un acte</a>
                <a href="' . route('login') . '" class="' . ($isLogin ? $navActive : $navBase) . '">Connexion</a>
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
