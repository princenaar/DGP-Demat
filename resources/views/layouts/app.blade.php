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
        $linkClass = 'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium transition ';
        $inactiveClass = 'border-transparent text-ink-700 hover:border-senegal-green hover:text-senegal-green';
        $activeClass = 'border-senegal-green text-senegal-green';
        $mobileBase = 'block px-4 py-3 text-base text-ink-700 hover:bg-ink-100 hover:text-senegal-green border-b border-ink-100 transition-colors';
        $mobileActive = 'block px-4 py-3 text-base font-semibold text-senegal-green bg-ink-100 border-b border-ink-100 border-l-4 border-l-senegal-green';
        $logoutDesktop = 'inline-flex items-center px-4 py-2 rounded-full border-2 border-senegal-red text-senegal-red font-semibold hover:bg-senegal-red hover:text-white transition';
        $logoutMobile = 'block w-full text-center px-4 py-3 rounded-full border-2 border-senegal-red text-senegal-red font-semibold hover:bg-senegal-red hover:text-white transition';

        $authNav = '<nav class="hidden md:flex items-center gap-6">';
        $authNav .= '<a href="' . route('demandes.index') . '" class="' . $linkClass . (request()->routeIs('demandes.index') ? $activeClass : $inactiveClass) . '">Mes demandes</a>';

        if (auth()->user()->hasRole('ADMIN')) {
            $authNav .= '<a href="' . route('settings.index') . '" class="' . $linkClass . (request()->routeIs('settings.*') ? $activeClass : $inactiveClass) . '">Paramètres</a>';
        }

        $authNav .= '<a href="' . route('profile.edit') . '" class="' . $linkClass . (request()->routeIs('profile.edit') ? $activeClass : $inactiveClass) . '">Profil</a>';
        $authNav .= '<form method="POST" action="' . route('logout') . '">' . csrf_field() . '<button type="submit" class="' . $logoutDesktop . '">Déconnexion</button></form>';
        $authNav .= '</nav>';

        $authNavMobile = '<nav class="flex flex-col">';
        $authNavMobile .= '<a href="' . route('demandes.index') . '" class="' . (request()->routeIs('demandes.index') ? $mobileActive : $mobileBase) . '">Mes demandes</a>';

        if (auth()->user()->hasRole('ADMIN')) {
            $authNavMobile .= '<a href="' . route('settings.index') . '" class="' . (request()->routeIs('settings.*') ? $mobileActive : $mobileBase) . '">Paramètres</a>';
        }

        $authNavMobile .= '<a href="' . route('profile.edit') . '" class="' . (request()->routeIs('profile.edit') ? $mobileActive : $mobileBase) . '">Profil</a>';
        $authNavMobile .= '<form method="POST" action="' . route('logout') . '" class="p-4">' . csrf_field() . '<button type="submit" class="' . $logoutMobile . '">Déconnexion</button></form>';
        $authNavMobile .= '</nav>';
    @endphp
    @include('layouts.partials.institutional-header', ['nav' => $authNav, 'navMobile' => $authNavMobile])

    <main class="flex-1 container mx-auto px-4 py-6">
        @hasSection('header')
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-ink-900">@yield('header')</h1>
            </header>
        @endif

        @if(session('status'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                {{ session('status') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    @include('layouts.partials.footer')
    @stack('scripts')
</body>
</html>
