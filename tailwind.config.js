import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // PRIMÁRIO (Os botões principais azuis)
                primary: {
                    50: '#e8f4fc',
                    100: '#d1e9f9',
                    400: '#5bc0de',
                    500: '#008cba',
                    600: '#007da0',
                    700: '#006480',
                    800: '#31708f',
                    900: '#1e4b63',
                },
                // ACCENT (Os botões vermelhos/vinho e logo)
                accent: {
                    50: '#fdf2f2',
                    100: '#fbe5e5',
                    500: '#d9534f',
                    600: '#c0392b',
                    700: '#a93226',
                    800: '#a4332b',
                    900: '#752823',
                },
                // SURFACE (Fundos e bordas)
                surface: {
                    50: '#f5f5f5',
                    100: '#eeeeee',
                    200: '#d2d6de',
                },

                // Novas Modalidades Semânticas
                premium: colors.amber, // Ou colors.amber para dourado
                success: colors.emerald,
                danger: colors.red,
                warning: colors.orange,
                info: colors.sky,
            }
        },
    },
    plugins: [forms],
};