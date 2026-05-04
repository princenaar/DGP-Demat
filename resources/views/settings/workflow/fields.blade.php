<label class="block text-sm font-semibold text-ink-700">
    État source
    <select name="etat_source_id" class="mt-1 w-full rounded-md border-gray-300" required>
        <option value="">Sélectionner l’état de départ</option>
        @foreach($etats as $etat)
            <option value="{{ $etat->id }}" @selected((int) old('etat_source_id', $transition->etat_source_id) === $etat->id)>{{ $etat->nom }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-normal text-ink-500">État actuel de la demande avant cette action.</span>
    @error('etat_source_id')
        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
    @enderror
</label>

<label class="block text-sm font-semibold text-ink-700">
    État cible
    <select name="etat_cible_id" class="mt-1 w-full rounded-md border-gray-300" required>
        <option value="">Sélectionner l’état d’arrivée</option>
        @foreach($etats as $etat)
            <option value="{{ $etat->id }}" @selected((int) old('etat_cible_id', $transition->etat_cible_id) === $etat->id)>{{ $etat->nom }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-normal text-ink-500">Nouvel état appliqué lorsque la transition est validée.</span>
    @error('etat_cible_id')
        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
    @enderror
</label>

<label class="block text-sm font-semibold text-ink-700">
    Rôle autorisé
    <select name="role_requis" class="mt-1 w-full rounded-md border-gray-300" required>
        @foreach($roles as $role)
            <option value="{{ $role }}" @selected(old('role_requis', $transition->role_requis) === $role)>{{ $role }}</option>
        @endforeach
    </select>
    <span class="mt-1 block text-xs font-normal text-ink-500">Seuls les utilisateurs ayant ce rôle pourront déclencher cette action.</span>
    @error('role_requis')
        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
    @enderror
</label>

<label class="block text-sm font-semibold text-ink-700">
    Ordre d’évaluation
    <input name="ordre" type="number" min="0" value="{{ old('ordre', $transition->ordre ?? 0) }}" class="mt-1 w-full rounded-md border-gray-300" required>
    <span class="mt-1 block text-xs font-normal text-ink-500">Priorité d’affichage et d’évaluation des transitions disponibles.</span>
    @error('ordre')
        <span class="mt-1 block text-xs font-semibold text-senegal-red">{{ $message }}</span>
    @enderror
</label>

<label class="flex items-start gap-3 rounded-md border border-ink-100 bg-ink-50 p-4 text-sm text-ink-700">
    <input type="checkbox" name="automatique" value="1" class="mt-1" @checked((bool) old('automatique', $transition->automatique))>
    <span>
        <span class="block font-semibold text-ink-900">Transition automatique</span>
        <span class="block text-xs text-ink-600">Si cochée, le moteur peut appliquer cette transition sans action manuelle lorsque ses conditions sont remplies.</span>
    </span>
</label>
