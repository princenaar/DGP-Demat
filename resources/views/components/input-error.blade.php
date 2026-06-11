@props(['messages'])

@php
    $flattenMessages = function (mixed $messages) use (&$flattenMessages): array {
        if ($messages instanceof \Illuminate\Contracts\Support\MessageProvider) {
            $messages = $messages->getMessageBag()->getMessages();
        }

        if ($messages instanceof \Illuminate\Support\MessageBag) {
            $messages = $messages->getMessages();
        }

        if ($messages instanceof \Illuminate\Support\Collection) {
            $messages = $messages->all();
        }

        if (is_array($messages)) {
            return collect($messages)
                ->flatMap(fn (mixed $message): array => $flattenMessages($message))
                ->all();
        }

        if (is_string($messages) || is_numeric($messages) || $messages instanceof \Stringable) {
            return [(string) $messages];
        }

        return [];
    };

    $messages = $flattenMessages($messages);
@endphp

@if ($messages !== [])
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ($messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
