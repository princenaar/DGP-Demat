<nav class="bg-white shadow" x-data="{ open: false }">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            {{-- Logo --}}
            <div class="flex items-center">
                <a href="{{ url('/') }}" class="flex items-center">
                    <img src="{{ asset('storage/images/logo_mshp.png') }}" alt="MSHP" class="h-10">
                    <span class="ml-3 text-xl font-bold text-senegal-green hidden md:inline">{{ config('app.name', 'Portail DRH') }}</span>
                </a>
            </div>

            {{-- Desktop Navigation --}}
            <div class="hidden md:flex items-center space-x-8">
                @auth
                    <x-nav-link href="{{ route('demandes.index') }}" :active="request()->routeIs('demandes.index')">
                        Mes demandes
                    </x-nav-link>
                    @if(auth()->user()->hasRole('ADMIN'))
                        <x-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.index')">
                            Utilisateurs
                        </x-nav-link>
                    @endif
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center text-sm font-medium text-ink-700 hover:text-senegal-green focus:outline-none">
                                <div>
                                    {{ auth()->user()->name }}
                                    <span class="text-xs text-ink-500">
                                        ({{ implode(', ', auth()->user()->getRoleNames()->toArray()) }})
                                    </span>
                                </div>
                                <svg class="ml-1 h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="{{ route('profile.edit') }}">Profil</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Déconnexion
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <x-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')">
                        Connexion
                    </x-nav-link>
                @endauth
            </div>

            {{-- Mobile menu button --}}
            <div class="md:hidden">
                <button @click="open = !open" class="text-ink-700 hover:text-senegal-green focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Mobile Navigation --}}
        <div class="md:hidden" x-show="open" x-transition>
            <div class="pt-2 pb-3 space-y-1">
                @auth
                    <x-responsive-nav-link href="{{ route('demandes.index') }}" :active="request()->routeIs('demandes.index')">
                        Mes demandes
                    </x-responsive-nav-link>
                    @if(auth()->user()->hasRole('ADMIN'))
                        <x-responsive-nav-link href="{{ route('users.index') }}" :active="request()->routeIs('users.index')">
                            Utilisateurs
                        </x-responsive-nav-link>
                    @endif
                    <x-responsive-nav-link href="{{ route('profile.edit') }}" :active="request()->routeIs('profile.edit')">
                        Profil
                    </x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                            Déconnexion
                        </x-responsive-nav-link>
                    </form>
                @else
                    <x-responsive-nav-link href="{{ route('login') }}" :active="request()->routeIs('login')">
                        Connexion
                    </x-responsive-nav-link>
                @endauth
            </div>
        </div>
    </div>
</nav>