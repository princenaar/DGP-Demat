@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <h1 class="mb-6 text-2xl font-bold text-ink-900">Vérification de l'authenticité de la demande</h1>

        @if($errors->any())
            <div class="mb-4 rounded border border-senegal-red bg-senegal-red/10 p-4 text-senegal-red">
                {{ $errors->first() }}
            </div>
        @else
            <div class="mb-4 rounded border border-senegal-green bg-senegal-green/10 p-4 text-senegal-green">
                Cette demande est authentique et a bien été signée.
            </div>

            <div class="rounded-lg bg-white p-6 shadow border border-gray-100">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-ink-700">Nom</dt>
                        <dd class="text-ink-900">{{ $demande->nom }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-ink-700">Prénom</dt>
                        <dd class="text-ink-900">{{ $demande->prenom }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-ink-700">Type de document</dt>
                        <dd class="text-ink-900">{{ $demande->typeDocument->nom ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-ink-700">Date de signature</dt>
                        <dd class="text-ink-900">{{ $demande->updated_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        @endif
    </div>
@endsection
