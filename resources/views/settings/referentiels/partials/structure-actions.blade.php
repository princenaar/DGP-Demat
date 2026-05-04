<div class="flex flex-wrap gap-3">
    <a class="font-semibold text-senegal-green" href="{{ route('settings.structures.edit', $structure) }}">Modifier</a>
    <form method="POST" action="{{ route('settings.structures.destroy', $structure) }}">
        @csrf
        @method('DELETE')
        <button class="font-semibold text-senegal-red" type="submit">Supprimer</button>
    </form>
</div>
