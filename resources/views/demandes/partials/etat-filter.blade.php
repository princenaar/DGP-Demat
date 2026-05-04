<div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <x-input-label :for="$filterId" value="État" />
        <select id="{{ $filterId }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-senegal-green focus:ring-senegal-green sm:w-72">
            <option value="">Tous les états</option>
            @foreach($etatOptions as $etatOption)
                <option value="{{ $etatOption['id'] }}">{{ $etatOption['label'] }}</option>
            @endforeach
        </select>
    </div>
</div>
