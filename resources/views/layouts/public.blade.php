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
        $mobileBase = 'block px-4 py-3 text-base text-ink-700 hover:bg-ink-100 hover:text-senegal-green border-b border-ink-100 transition-colors';
        $mobileActive = 'block px-4 py-3 text-base font-semibold text-senegal-green bg-ink-100 border-b border-ink-100 border-l-4 border-l-senegal-green';
        $ctaDesktop = 'inline-flex items-center px-4 py-2 rounded-full bg-senegal-green text-white font-semibold shadow-sm hover:opacity-90 transition';
        $ctaMobile = 'block text-center px-4 py-3 rounded-full bg-senegal-green text-white font-semibold shadow-sm hover:opacity-90 transition';
        $isHome = request()->path() === '/';
        $isCreate = request()->routeIs('demandes.create');
        $isLogin = request()->routeIs('login');
        $publicNav = '
            <nav class="hidden md:flex items-center space-x-6 text-base">
                <a href="' . url('/') . '" class="' . ($isHome ? $navActive : $navBase) . '">Accueil</a>
                <a href="' . route('demandes.create') . '" class="' . ($isCreate ? $navActive : $navBase) . '">Faire une demande</a>
                <a href="' . url('/#verification') . '" class="' . $navBase . '">Vérifier un acte</a>
                <a href="' . route('login') . '" class="' . $ctaDesktop . '">Espace administrateur</a>
            </nav>
        ';
        $publicNavMobile = '
            <nav class="flex flex-col">
                <a href="' . url('/') . '" class="' . ($isHome ? $mobileActive : $mobileBase) . '">Accueil</a>
                <a href="' . route('demandes.create') . '" class="' . ($isCreate ? $mobileActive : $mobileBase) . '">Faire une demande</a>
                <a href="' . url('/#verification') . '" class="' . $mobileBase . '">Vérifier un acte</a>
                <div class="p-4">
                    <a href="' . route('login') . '" class="' . $ctaMobile . '">Espace administrateur</a>
                </div>
            </nav>
        ';
    @endphp
    @include('layouts.partials.institutional-header', ['nav' => $publicNav, 'navMobile' => $publicNavMobile])

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
