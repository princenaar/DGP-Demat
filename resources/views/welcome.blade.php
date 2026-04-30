@extends('layouts.public')

@section('content')
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-paper">
        {{-- Layered backdrop: existing background image, masked to top-right; soft radial; dotted grid --}}
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -right-32 -top-32 h-[480px] w-[480px] rounded-full bg-gradient-to-br from-senegal-green/10 to-transparent blur-3xl"></div>
            <div class="absolute right-0 top-0 hidden h-full w-1/2 bg-[url('/storage/images/background.png')] bg-cover bg-center opacity-[0.06] lg:block"></div>
            <div class="absolute inset-0 opacity-[0.35]"
                 style="background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,0.08) 1px, transparent 0); background-size: 28px 28px;"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 pb-20 pt-16 sm:px-6 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:pt-24">
            {{-- Left column: copy + CTAs --}}
            <div class="lg:col-span-7">
                <div class="inline-flex items-center gap-2">
                    <span class="h-px w-8 bg-senegal-green"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">DRH · MSHP</span>
                </div>

                <h1 class="mt-5 text-4xl font-semibold leading-[1.05] tracking-tight text-ink-900 sm:text-5xl lg:text-6xl xl:text-7xl">
                    Portail de
                    <span class="relative inline-block">
                        <span class="relative z-10">dématérialisation</span>
                        <span class="absolute inset-x-0 bottom-1 -z-0 h-3 bg-senegal-yellow/60 sm:bottom-2 sm:h-4"></span>
                    </span>
                    des actes&nbsp;administratifs.
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-ink-700">
                    Déposez, suivez et vérifiez les actes administratifs traités par la
                    Direction des Ressources Humaines. Une fois signés, vos documents vous
                    sont envoyés directement par <span class="font-semibold text-ink-900">email</span> —
                    plus aucun déplacement nécessaire.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('demandes.create') }}"
                       class="group inline-flex items-center justify-center gap-2 rounded-md bg-senegal-green px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-green-800 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-senegal-green focus:ring-offset-2">
                        Faire une demande
                        <svg class="h-4 w-4 transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a href="#process"
                       class="inline-flex items-center justify-center gap-2 rounded-md border border-ink-900/15 bg-white px-6 py-3.5 text-sm font-semibold text-ink-900 transition hover:-translate-y-0.5 hover:border-senegal-green hover:text-senegal-green focus:outline-none focus:ring-2 focus:ring-senegal-green focus:ring-offset-2">
                        Comment ça marche
                    </a>
                </div>

                {{-- Trust strip --}}
                <dl class="mt-10 flex flex-wrap items-center gap-x-8 gap-y-3 text-sm text-ink-700">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-senegal-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>Service officiel du Ministère</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-senegal-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Dépôt 24h / 24</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-senegal-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="18" y="14" width="3" height="3"/><rect x="14" y="18" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/></svg>
                        <span>Vérification par QR code</span>
                    </div>
                </dl>
            </div>

            {{-- Right column: layered "document → email" composition --}}
            <div class="relative lg:col-span-5">
                <div class="relative mx-auto h-[420px] w-full max-w-md sm:h-[460px]">
                    {{-- Decorative background tile (yellow accent) --}}
                    <div class="absolute right-2 top-6 h-72 w-72 -rotate-6 rounded-2xl bg-senegal-yellow/30"></div>

                    {{-- Envelope card (back) --}}
                    <div class="absolute bottom-6 right-0 w-72 rotate-3 rounded-2xl border border-ink-900/10 bg-white p-5 shadow-xl">
                        <div class="flex items-center gap-2 border-b border-ink-900/10 pb-3 text-xs uppercase tracking-[0.18em] text-ink-500">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                            <span>Boîte mail</span>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-ink-900">Votre attestation est prête</p>
                        <p class="mt-1 text-xs text-ink-700">drh@sante.gouv.sn</p>
                        <div class="mt-3 flex items-center gap-2 rounded-md bg-paper px-3 py-2 text-xs text-ink-700">
                            <svg class="h-3.5 w-3.5 text-senegal-green" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            attestation_signee.pdf
                        </div>
                    </div>

                    {{-- Document card (front) --}}
                    <div class="absolute left-0 top-0 w-72 -rotate-3 rounded-2xl border border-ink-900/10 bg-white shadow-2xl sm:w-80">
                        {{-- Document header bar --}}
                        <div class="flex items-center justify-between rounded-t-2xl bg-senegal-green px-5 py-3">
                            <div class="flex items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-white/90">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <span>République du Sénégal</span>
                            </div>
                            <span class="h-2 w-2 rounded-full bg-senegal-yellow"></span>
                        </div>
                        <div class="px-5 py-4">
                            <p class="text-[11px] font-medium uppercase tracking-wider text-ink-500">Attestation de travail</p>
                            <p class="mt-1 text-sm font-semibold text-ink-900">N° 2026 / DRH / 04217</p>
                            <div class="mt-4 space-y-1.5">
                                <div class="h-1.5 w-full rounded bg-ink-900/10"></div>
                                <div class="h-1.5 w-11/12 rounded bg-ink-900/10"></div>
                                <div class="h-1.5 w-9/12 rounded bg-ink-900/10"></div>
                                <div class="h-1.5 w-10/12 rounded bg-ink-900/10"></div>
                            </div>
                            <div class="mt-5 flex items-end justify-between">
                                <div>
                                    <p class="text-[10px] uppercase tracking-wider text-ink-500">Signé le</p>
                                    <p class="text-xs font-semibold text-ink-900">30 / 04 / 2026</p>
                                </div>
                                {{-- QR placeholder built from a 5x5 grid --}}
                                <div class="grid h-16 w-16 grid-cols-5 grid-rows-5 gap-px rounded-md bg-ink-900 p-1.5">
                                    @php
                                        $qr = [
                                            [1,1,1,0,1],
                                            [1,0,1,1,1],
                                            [1,1,0,1,0],
                                            [0,1,1,0,1],
                                            [1,0,1,1,1],
                                        ];
                                    @endphp
                                    @foreach($qr as $row)
                                        @foreach($row as $cell)
                                            <span class="{{ $cell ? 'bg-white' : 'bg-ink-900' }} rounded-[1px]"></span>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating "verified" badge --}}
                    <div class="absolute -bottom-2 left-8 flex items-center gap-2 rounded-full border border-ink-900/10 bg-white px-3 py-2 shadow-lg">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-senegal-green">
                            <svg class="h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="text-xs font-semibold text-ink-900">Acte authentifié</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Comment ça marche --}}
    <section id="process" class="relative bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <div class="inline-flex items-center gap-2">
                    <span class="h-px w-8 bg-senegal-green"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">Comment ça marche</span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                    De la demande à votre boîte mail, en trois étapes.
                </h2>
                <p class="mt-4 text-base text-ink-700">
                    Aucun déplacement, aucun papier. Vous suivez l'avancement en ligne et
                    recevez votre acte signé dès qu'il est validé.
                </p>
            </div>

            <ol class="mt-14 grid grid-cols-1 gap-px overflow-hidden rounded-2xl border border-ink-900/10 bg-ink-900/10 md:grid-cols-3">
                {{-- Step 1 --}}
                <li class="group flex flex-col bg-white p-8 transition hover:bg-paper">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-500">Étape 01</span>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-senegal-green/10 text-senegal-green">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        </span>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-ink-900">Soumettez votre demande</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink-700">
                        Renseignez le formulaire en ligne, joignez vos pièces justificatives
                        et choisissez le type d'acte souhaité. Aucun compte n'est requis.
                    </p>
                    <a href="{{ route('demandes.create') }}" class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-senegal-green hover:underline">
                        Démarrer
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                </li>

                {{-- Step 2 --}}
                <li class="group flex flex-col bg-white p-8 transition hover:bg-paper">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-500">Étape 02</span>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-senegal-green/10 text-senegal-green">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.22-8.56"/><polyline points="21 4 21 12 13 12"/></svg>
                        </span>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-ink-900">Traitement et signature</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink-700">
                        Les services de la DRH instruisent votre dossier. Si nécessaire, un
                        agent vous demande des compléments par email — vous restez informé
                        à chaque étape.
                    </p>
                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-ink-500">
                        Suivi automatique
                    </span>
                </li>

                {{-- Step 3 --}}
                <li class="group flex flex-col bg-white p-8 transition hover:bg-paper">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-ink-500">Étape 03</span>
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-senegal-green/10 text-senegal-green">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        </span>
                    </div>
                    <h3 class="mt-6 text-xl font-semibold text-ink-900">Réception par email</h3>
                    <p class="mt-3 text-sm leading-relaxed text-ink-700">
                        Dès la signature, votre acte officiel — au format PDF avec QR code —
                        vous est envoyé directement par email. Téléchargez-le, imprimez-le
                        ou transmettez-le en toute simplicité.
                    </p>
                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-senegal-green">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                        Aucun déplacement
                    </span>
                </li>
            </ol>
        </div>
    </section>

    {{-- À propos de la DRH --}}
    <section class="bg-paper py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-16">
                <div class="lg:col-span-4">
                    <div class="inline-flex items-center gap-2">
                        <span class="h-px w-8 bg-senegal-green"></span>
                        <span class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">À propos</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-ink-900 sm:text-4xl">
                        La Direction des Ressources Humaines.
                    </h2>
                </div>
                <div class="lg:col-span-8">
                    <p class="text-lg leading-relaxed text-ink-700">
                        Un service numérique pour fluidifier les procédures RH du
                        ministère. La DRH du Ministère de la Santé et de l'Hygiène
                        publique met à disposition de ses agents et des citoyens un
                        portail unique pour le dépôt, le traitement et la délivrance
                        électronique des actes administratifs.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-x-10 gap-y-4 border-t border-ink-900/10 pt-6 text-sm text-ink-700">
                        <span class="font-semibold tracking-[0.18em] text-ink-900">MSHP</span>
                        <span class="h-4 w-px bg-ink-900/15"></span>
                        <span class="font-semibold tracking-[0.18em] text-ink-900">DRH</span>
                        <span class="h-4 w-px bg-ink-900/15"></span>
                        <span>Fann Résidence, Rue Aimé Césaire — Dakar</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Stats band --}}
    <section class="bg-white py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <dl class="grid grid-cols-1 divide-y divide-ink-900/10 overflow-hidden rounded-2xl border border-ink-900/10 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                <div class="flex flex-col gap-2 px-8 py-8">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">Disponibilité</dt>
                    <dd class="font-mono text-4xl font-semibold tracking-tight text-ink-900 sm:text-5xl">24h<span class="text-ink-500">/</span>24</dd>
                    <p class="text-sm text-ink-700">Dépôt des demandes en ligne</p>
                </div>
                <div class="flex flex-col gap-2 px-8 py-8">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">Catalogue</dt>
                    <dd class="font-mono text-4xl font-semibold tracking-tight text-ink-900 sm:text-5xl">05</dd>
                    <p class="text-sm text-ink-700">Documents dématérialisés disponibles</p>
                </div>
                <div class="flex flex-col gap-2 px-8 py-8">
                    <dt class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-green">Sécurité</dt>
                    <dd class="font-mono text-4xl font-semibold tracking-tight text-ink-900 sm:text-5xl">QR</dd>
                    <p class="text-sm text-ink-700">Vérification des actes signés</p>
                </div>
            </dl>
        </div>
    </section>

    {{-- Verification band --}}
    <section id="verification" class="relative overflow-hidden bg-ink-900 py-20 text-white sm:py-24">
        <div class="pointer-events-none absolute inset-0 opacity-[0.08]"
             style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.6) 1px, transparent 0); background-size: 24px 24px;"></div>
        <div class="absolute -left-24 top-1/2 h-96 w-96 -translate-y-1/2 rounded-full bg-senegal-green/30 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 sm:px-6 lg:grid-cols-12 lg:px-8">
            <div class="lg:col-span-6">
                <div class="inline-flex items-center gap-2">
                    <span class="h-px w-8 bg-senegal-yellow"></span>
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-senegal-yellow">Vérifier un acte</span>
                </div>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                    Authentifiez un acte signé en quelques secondes.
                </h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-white/75">
                    Chaque acte délivré par la DRH comporte un QR code et un identifiant
                    unique. Scannez le QR code ou saisissez le code ci-contre pour
                    contrôler son authenticité.
                </p>
            </div>

            <div class="lg:col-span-6">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur sm:p-8"
                     x-data="{ code: '' }">
                    <label for="verif-code" class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                        Code de l'acte
                    </label>
                    <form class="mt-3 flex flex-col gap-3 sm:flex-row"
                          x-on:submit.prevent="if (code.trim()) window.location.href = '{{ url('/demandes/verifier') }}/' + encodeURIComponent(code.trim())">
                        <input id="verif-code"
                               x-model="code"
                               type="text"
                               required
                               autocomplete="off"
                               placeholder="Ex. A1B2C3D4"
                               class="flex-1 rounded-md border-0 bg-white px-4 py-3 font-mono text-sm tracking-wider text-ink-900 placeholder:text-ink-500/60 focus:ring-2 focus:ring-senegal-yellow focus:ring-offset-2 focus:ring-offset-ink-900">
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-md bg-senegal-yellow px-6 py-3 text-sm font-semibold text-ink-900 transition hover:-translate-y-0.5 hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-senegal-yellow focus:ring-offset-2 focus:ring-offset-ink-900">
                            Vérifier
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </button>
                    </form>
                    <p class="mt-4 flex items-center gap-2 text-xs text-white/60">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Le code figure en bas de l'acte signé, à côté du QR code.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
