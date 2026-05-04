<div class="flex flex-wrap gap-3">
    <a class="font-semibold text-senegal-green" href="{{ route('settings.users.edit', $user) }}">Modifier</a>
    <form method="POST" action="{{ route('settings.users.reset-password', $user) }}">
        @csrf
        <button class="font-semibold text-senegal-green" type="submit">Réinitialiser</button>
    </form>
    @if($user->is_active)
        <form method="POST" action="{{ route('settings.users.destroy', $user) }}">
            @csrf
            @method('DELETE')
            <button class="font-semibold text-senegal-red" type="submit">Désactiver</button>
        </form>
    @else
        <form method="POST" action="{{ route('settings.users.reactivate', $user) }}">
            @csrf
            <button class="font-semibold text-senegal-green" type="submit">Réactiver</button>
        </form>
    @endif
</div>
