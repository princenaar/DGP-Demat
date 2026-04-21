import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
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
                ink: { 900: '#0F172A', 700: '#334155', 500: '#64748B' },
                paper: '#F8FAFC',
            },
        },
    },

    plugins: [forms],
};
