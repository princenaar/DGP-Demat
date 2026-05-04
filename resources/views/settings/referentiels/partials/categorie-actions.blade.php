<div class="flex flex-wrap gap-3">
    <a class="font-semibold text-senegal-green" href="{{ route('settings.categories.edit', $categorie) }}">Modifier</a>
    <form method="POST" action="{{ route('settings.categories.destroy', $categorie) }}">
        @csrf
        @method('DELETE')
        <button class="font-semibold text-senegal-red" type="submit">Supprimer</button>
    </form>
</div>
