import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    50: '#fdf2f2',
                    100: '#fde6e7',
                    200: '#fad0d2',
                    300: '#f5abb0',
                    400: '#ec767e',
                    500: '#dc4751',
                    600: '#a91721', // Primary brand color
                    700: '#8e121a',
                    800: '#751319',
                    900: '#63151a',
                    950: '#380609',
                },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
