@php
    $nav = $nav ?? '';
    $navMobile = $navMobile ?? '';
    $hasMobileNav = trim($navMobile) !== '';
@endphp
<div x-data="{ open: false }" class="bg-white">
    <div class="py-4 px-4">
        <div class="container mx-auto flex justify-between items-center gap-4">
            <a href="{{ url('/') }}" class="flex items-center gap-4 group" aria-label="Retour à l'accueil">
                <img src="{{ asset('storage/images/logo_mshp.png') }}" alt="Ministère de la Santé et de l'Hygiène publique" class="h-12 md:h-16">
                <div class="hidden md:block">
                    <div class="text-sm uppercase tracking-wide text-ink-700 group-hover:text-senegal-green transition-colors">Ministère de la Santé et de l'Hygiène publique</div>
                    <div class="text-xl font-bold text-senegal-green">Direction des Ressources Humaines</div>
                </div>
            </a>

            <div class="flex items-center">
                {!! $nav !!}

                @if($hasMobileNav)
                <button
                    type="button"
                    class="md:hidden inline-flex items-center justify-center p-2 rounded text-ink-700 hover:text-senegal-green hover:bg-ink-100 focus:outline-none focus:ring-2 focus:ring-senegal-green transition"
                    @click="open = !open"
                    :aria-expanded="open ? 'true' : 'false'"
                    aria-controls="institutional-mobile-menu"
                    aria-label="Menu"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                @endif
            </div>
        </div>
    </div>

    @if($hasMobileNav)
    <div
        id="institutional-mobile-menu"
        class="md:hidden border-t border-ink-100 bg-white"
        x-show="open"
        x-transition
        x-cloak
    >
        {!! $navMobile !!}
    </div>
    @endif

    <div class="h-1 bg-senegal-green"></div>
</div>
