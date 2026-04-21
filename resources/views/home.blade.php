@extends('layouts.app')

@section('header')
    Tableau de bord
@endsection

@section('content')
    <div class="max-w-2xl mx-auto mt-8 px-4">
        <div class="bg-white rounded-lg shadow p-8 border border-gray-100">
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <p class="text-ink-900">
                {{ __('Vous êtes connecté.') }}
            </p>
        </div>
    </div>
@endsection
