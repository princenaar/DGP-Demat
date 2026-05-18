import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        'bg-senegal-yellow',
        'bg-blue-100',
        'bg-senegal-green',
        'bg-senegal-red',
        'bg-amber-100',
        'bg-indigo-100',
        'bg-green-700',
        'bg-gray-300',
        'bg-gray-200',
        'text-ink-900',
        'text-blue-800',
        'text-white',
        'text-amber-900',
        'text-indigo-800',
        'text-gray-800',
        'text-ink-700',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                senegal: {
                    green:  '#00853F',
                    yellow: '#FDEF42',
                    red:    '#E31B23',
                },
                ink: { 900: '#0F172A', 700: '#334155', 500: '#64748B', 100: '#F1F5F9' },
                paper: '#F8FAFC',
            },
        },
    },

    plugins: [forms],
};
