@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-senegal-green focus:ring-senegal-green rounded-md shadow-sm']) }}>
