<x-guest-layout>
    <div class="space-y-4 text-sm text-ink-700">
        @if (session('resent'))
            <div class="rounded border border-senegal-green bg-senegal-green/10 p-4 text-senegal-green">
                {{ __('A fresh verification link has been sent to your email address.') }}
            </div>
        @endif

        <p>{{ __('Before proceeding, please check your email for a verification link.') }}</p>
        <p>{{ __('If you did not receive the email') }},</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="font-medium text-senegal-green hover:text-green-800">
                {{ __('click here to request another') }}
            </button>
        </form>
    </div>
</x-guest-layout>
