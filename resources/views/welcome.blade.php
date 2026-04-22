@extends('layouts.public')

@section('content')
    <section class="relative overflow-hidden bg-paper">
        <div class="absolute inset-0 bg-[url('/storage/images/background.png')] bg-cover bg-center opacity-10"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase text-senegal-green">DRH / MSHP</p>
                <h1 class="mt-3 text-4xl font-bold tracking-normal text-ink-900 sm:text-5xl">
                    Portail de dématérialisation des actes administratifs
                </h1>
                <p class="mt-5 max-w-2xl text-lg text-ink-700">
                    Déposez, suivez et vérifiez les actes administratifs traités par la Direction des Ressources Humaines.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('demandes.create') }}" class="inline-flex items-center justify-center rounded-md bg-senegal-green px-5 py-3 text-sm font-semibold text-white shadow hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-senegal-green focus:ring-offset-2">
                        Faire une demande
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md border border-senegal-green px-5 py-3 text-sm font-semibold text-senegal-green hover:bg-senegal-green hover:text-white focus:outline-none focus:ring-2 focus:ring-senegal-green focus:ring-offset-2">
                        Espace agent
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="verification" class="bg-white py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('demandes.create') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-senegal-green hover:shadow">
                    <h2 class="text-lg font-semibold text-ink-900">Faire une demande</h2>
                    <p class="mt-3 text-sm text-ink-700">Soumettre une nouvelle demande d’acte administratif.</p>
                </a>
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-ink-900">Vérifier un acte</h2>
                    <p class="mt-3 text-sm text-ink-700">Scannez le QR code de l’acte signé pour contrôler son authenticité.</p>
                </div>
                <a href="{{ route('demandes.create') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-senegal-green hover:shadow">
                    <h2 class="text-lg font-semibold text-ink-900">Espace agent</h2>
                    <p class="mt-3 text-sm text-ink-700">Accéder au suivi et au traitement des dossiers.</p>
                </a>
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-ink-900">À propos de la DRH</h2>
                    <p class="mt-3 text-sm text-ink-700">Un service numérique pour fluidifier les procédures RH du ministère.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-paper py-10">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 px-4 sm:grid-cols-3 sm:px-6 lg:px-8">
            <div class="border-t-4 border-senegal-yellow bg-white p-5 shadow-sm">
                <p class="text-3xl font-bold text-senegal-green">24h/24</p>
                <p class="mt-2 text-sm text-ink-700">Dépôt des demandes en ligne</p>
            </div>
            <div class="border-t-4 border-senegal-yellow bg-white p-5 shadow-sm">
                <p class="text-3xl font-bold text-senegal-green">5</p>
                <p class="mt-2 text-sm text-ink-700">Demande de documents dématérialisés</p>
            </div>
            <div class="border-t-4 border-senegal-yellow bg-white p-5 shadow-sm">
                <p class="text-3xl font-bold text-senegal-green">QR</p>
                <p class="mt-2 text-sm text-ink-700">Vérification des actes signés</p>
            </div>
        </div>
    </section>
@endsection
