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
        'bg-stone-600', 'hover:bg-stone-500', 'bg-stone-800', 'hover:bg-stone-700',
        'bg-amber-600', 'hover:bg-amber-500',
        'bg-blue-600', 'hover:bg-blue-500',
        'bg-green-600', 'hover:bg-green-500',
        'bg-red-100', 'hover:bg-red-200', 'text-red-700',
        'bg-purple-600', 'hover:bg-purple-500',
        'bg-indigo-600', 'hover:bg-indigo-500',
        'bg-orange-600', 'hover:bg-orange-500',
        'bg-teal-600', 'hover:bg-teal-500',
        'text-white',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
