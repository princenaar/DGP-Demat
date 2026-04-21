<div class="bg-white py-4 px-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center gap-4">
            <img src="{{ asset('storage/images/logo_mshp.png') }}" alt="Ministère de la Santé et de l'Hygiène publique" class="h-16">
            <div class="hidden md:block">
                <div class="text-sm uppercase tracking-wide text-ink-700">Ministère de la Santé et de l'Hygiène publique</div>
                <div class="text-xl font-bold text-senegal-green">Direction des Ressources Humaines</div>
            </div>
        </div>
        <div class="flex items-center">
            {!! $nav ?? '' !!}
        </div>
    </div>
</div>
<div class="h-1 bg-senegal-green"></div>
