@php
    use App\Models\EtatDemande;
    use Carbon\Carbon;

    $etat = $demande->etatDemande->nom ?? 'N/A';
    $badgeClass = match ($etat) {
        EtatDemande::VALIDEE, EtatDemande::SIGNEE => 'bg-senegal-green text-white',
        EtatDemande::REFUSEE => 'bg-senegal-red text-white',
        EtatDemande::EN_ATTENTE, EtatDemande::RECEPTIONNEE => 'bg-senegal-yellow text-ink-900',
        default => 'bg-gray-200 text-ink-700',
    };
@endphp

@extends('layouts.app')

@section('header')
    Détails de la demande
@endsection

@section('content')
    <div class="space-y-6" x-data="{ etatModalOpen: false, fichiersOpen: false, nouvelEtat: '', agentVisible: false, commentaire: '' }">
        @if(session('success'))
            <div class="rounded bg-senegal-green/10 border-l-4 border-senegal-green p-4 text-senegal-green font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="lg:col-span-2 bg-white rounded-lg shadow divide-y divide-gray-100 border border-gray-100">
                <div class="p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm text-ink-500">Demande #{{ $demande->id }}</p>
                            <h2 class="mt-1 text-xl font-semibold text-ink-900">{{ $demande->typeDocument->nom ?? 'N/A' }}</h2>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}">
                            {{ $etat }}
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Nom</dt>
                            <dd class="text-ink-900">{{ $demande->nom ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Prénom</dt>
                            <dd class="text-ink-900">{{ $demande->prenom ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Email</dt>
                            <dd class="text-ink-900">{{ $demande->email ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Téléphone</dt>
                            <dd class="text-ink-900">{{ $demande->telephone ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Statut</dt>
                            <dd class="text-ink-900">{{ $demande->statut ? ucfirst($demande->statut) : 'N/A' }}</dd>
                        </div>
                        @if($demande->statut === 'étatique')
                            <div>
                                <dt class="text-sm font-medium text-ink-700">Matricule</dt>
                                <dd class="text-ink-900">{{ $demande->matricule ?? 'N/A' }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-ink-700">NIN</dt>
                            <dd class="text-ink-900">{{ $demande->nin ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Structure</dt>
                            <dd class="text-ink-900">{{ $demande->structure->nom ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Catégorie socioprofessionnelle</dt>
                            <dd class="text-ink-900">{{ $demande->categorieSocioprofessionnelle?->libelle ?? 'N/A' }}</dd>
                        </div>
                        @if($demande->date_naissance)
                            <div>
                                <dt class="text-sm font-medium text-ink-700">Date de naissance</dt>
                                <dd class="text-ink-900">{{ Carbon::parse($demande->date_naissance)->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($demande->date_prise_service)
                            <div>
                                <dt class="text-sm font-medium text-ink-700">Date de prise de service</dt>
                                <dd class="text-ink-900">{{ Carbon::parse($demande->date_prise_service)->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($demande->date_fin_service)
                            <div>
                                <dt class="text-sm font-medium text-ink-700">Date de fin de service</dt>
                                <dd class="text-ink-900">{{ Carbon::parse($demande->date_fin_service)->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        @if($demande->date_depart_retraite)
                            <div>
                                <dt class="text-sm font-medium text-ink-700">Date de départ à la retraite</dt>
                                <dd class="text-ink-900">{{ Carbon::parse($demande->date_depart_retraite)->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Date de création</dt>
                            <dd class="text-ink-900">{{ $demande->created_at ? $demande->created_at->format('d/m/Y H:i') : 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-ink-700">Dernière modification</dt>
                            <dd class="text-ink-900">{{ $demande->updated_at ? $demande->updated_at->format('d/m/Y H:i') : 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="p-6">
                    <h3 class="text-base font-semibold text-ink-900">Commentaire</h3>
                    <div class="mt-3 font-mono text-sm bg-gray-50 rounded p-4 whitespace-pre-wrap text-ink-700">{{ $demande->commentaire ?? 'N/A' }}</div>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-ink-900">Fichiers justificatifs</h3>
                        @if($demande->justificatifs && $demande->justificatifs->count())
                            <button type="button" class="text-sm font-medium text-senegal-green hover:text-green-800" x-on:click="fichiersOpen = ! fichiersOpen">
                                Voir les fichiers ({{ $demande->justificatifs->count() }})
                            </button>
                        @endif
                    </div>

                    @if($demande->justificatifs && $demande->justificatifs->count())
                        <div class="mt-4 space-y-4" x-show="fichiersOpen" x-cloak>
                            @foreach($demande->justificatifs as $fichier)
                                <div class="rounded border border-gray-200 p-4" x-data="{ open: false }">
                                    <button type="button" class="text-left text-sm font-medium text-senegal-green hover:text-green-800" x-on:click="open = ! open">
                                        {{ $fichier->nom }} ({{ $fichier->mime_type }}, {{ number_format($fichier->taille / 1024, 2) }} Ko)
                                    </button>
                                    <iframe x-show="open" x-cloak src="{{ route('justificatifs.voir', $fichier->id) }}" class="mt-3 h-96 w-full rounded border border-gray-200"></iframe>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-3 text-sm text-ink-700">N/A</p>
                    @endif

                    <a href="{{ route('demandes.index') }}" class="mt-6 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-ink-700 shadow-sm hover:bg-gray-50">
                        Retour à la liste
                    </a>
                </div>
            </section>

            <aside class="bg-white rounded-lg shadow border border-gray-100 p-6 h-fit">
                <h2 class="text-lg font-semibold text-ink-900">Actions</h2>
                <div class="mt-4 space-y-3">
                    @if($etat == EtatDemande::EN_ATTENTE && auth()->user()->hasRole('ACCUEIL'))
                        <x-primary-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'RECEPTIONNEE'; agentVisible = false; etatModalOpen = true">Réceptionner la demande</x-primary-button>
                    @elseif($etat == EtatDemande::RECEPTIONNEE && auth()->user()->hasRole('CHEF_DE_DIVISION'))
                        <x-primary-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'VALIDEE'; agentVisible = true; etatModalOpen = true">Valider la demande</x-primary-button>
                        <x-danger-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'REFUSEE'; agentVisible = false; etatModalOpen = true">Refuser la demande</x-danger-button>
                    @elseif($etat === EtatDemande::VALIDEE && auth()->id() === $demande->agent_id)
                        <x-primary-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'EN SIGNATURE'; agentVisible = false; etatModalOpen = true">Envoyer en signature</x-primary-button>
                        <button type="button" class="inline-flex w-full justify-center rounded-md bg-senegal-yellow px-4 py-2 text-xs font-semibold uppercase tracking-widest text-ink-900 hover:bg-yellow-300" x-on:click="nouvelEtat = 'DEMANDE DE COMPLEMENTS'; agentVisible = false; etatModalOpen = true">Demander des compléments</button>
                    @elseif($etat === EtatDemande::EN_SIGNATURE && auth()->user()->hasRole('DRH'))
                        <x-primary-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'SIGNEE'; agentVisible = false; etatModalOpen = true">Signer la demande</x-primary-button>
                        <x-danger-button type="button" class="w-full justify-center" x-on:click="nouvelEtat = 'SUSPENDUE'; agentVisible = false; etatModalOpen = true">Suspendre la demande</x-danger-button>
                    @else
                        <span class="text-sm text-ink-500">Aucune action disponible.</span>
                    @endif
                </div>
            </aside>
        </div>

        <section class="bg-white rounded-lg shadow border border-gray-100 p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-semibold text-ink-900">Aperçu du document final</h2>
                @if($pdfBase64)
                    <a href="data:application/pdf;base64,{{ $pdfBase64 }}" download="Demande_{{ $demande->id }}.pdf" class="inline-flex items-center justify-center rounded-md bg-senegal-green px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                        Télécharger le PDF
                    </a>
                @endif
            </div>

            <div class="mt-4">
                @if($pdfBase64)
                    <iframe src="data:application/pdf;base64,{{ $pdfBase64 }}" class="h-[600px] w-full rounded border border-gray-200"></iframe>
                @elseif($etat === EtatDemande::SIGNEE)
                    <iframe src="{{ route('demandes.voirPdf', $demande->id) }}" class="h-[600px] w-full rounded border border-gray-200"></iframe>
                @else
                    <p class="text-sm text-ink-700">Aucun aperçu disponible pour cette demande.</p>
                @endif
            </div>
        </section>

        <div x-show="etatModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-ink-900/60 px-4">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl" x-on:click.outside="etatModalOpen = false">
                <h2 class="text-lg font-semibold text-ink-900">Confirmation du changement d’état</h2>

                <form method="POST" action="{{ route('demandes.changerEtat', $demande->id) }}" class="mt-5 space-y-4">
                    @csrf
                    <input type="hidden" name="nouvel_etat" x-bind:value="nouvelEtat">

                    <div x-show="agentVisible" x-cloak>
                        <x-input-label for="agent_id" value="Imputer à un agent" />
                        <select name="agent_id" id="agent_id" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green">
                            <option value="">Sélectionner un agent</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="commentaire" value="Commentaire" />
                        <textarea name="commentaire" id="commentaire" x-model="commentaire" class="mt-1 block w-full rounded-md border-gray-300 focus:border-senegal-green focus:ring-senegal-green" rows="4" required></textarea>
                    </div>

                    <div>
                        <p class="text-sm text-ink-500">Texte rapide</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach(['Urgence signalée', "M'en parler", 'Justificatifs invalides', 'Dossier incomplet', 'Rien à signaler'] as $texte)
                                <button type="button" class="rounded border border-gray-300 px-3 py-1 text-xs font-medium text-ink-700 hover:bg-gray-50" x-on:click="commentaire = @js($texte)">
                                    {{ $texte }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-secondary-button x-on:click="etatModalOpen = false">Annuler</x-secondary-button>
                        <x-primary-button>Confirmer</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
