@extends('layouts.app')

@section('header')
    Tableau de bord
@endsection

@section('content')
    <div class="max-w-4xl">
        <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
            <p class="text-ink-900">
                {{ __('Vous êtes connecté.') }}
            </p>
        </div>
    </div>
@endsection
