<div class="bg-senegal-green text-white text-sm py-2 px-4">
    <div class="container mx-auto flex justify-between items-center">
        <div class="flex items-center gap-2">
            <img src="{{ asset('storage/images/senegal-flag.png') }}" alt="Drapeau du Sénégal" class="h-4">
            <span>République du Sénégal — Un peuple, Un But, Une Foi</span>
        </div>
        <div class="text-xs">
            {{-- Environment / locale stub --}}
            @if(app()->environment('local'))
                <span class="bg-senegal-yellow text-senegal-green px-2 py-0.5 rounded">LOCAL</span>
            @else
                <span>FR</span>
            @endif
        </div>
    </div>
</div>
<div class="h-0.5 bg-senegal-yellow"></div>