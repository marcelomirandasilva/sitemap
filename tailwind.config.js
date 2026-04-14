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
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    primary: '#0E4F8C',
                    secondary: '#1670B8',
                    accent: '#16B8B0',
                    accentSoft: '#52D6D0',
                    gradientStart: '#0E4F8C',
                    gradientEnd: '#18C2B7',
                },
                bg: {
                    page: '#FFFFFF',
                    subtle: '#F7FAFC',
                    muted: '#EEF4F7',
                },
                border: {
                    soft: '#E8EEF3',
                    strong: '#C8D4DE',
                },
                text: {
                    primary: '#183247',
                    secondary: '#5F7486',
                },
                primary: {
                    50: '#F7FAFC',
                    100: '#EEF4F7',
                    400: '#52D6D0',
                    500: '#1670B8',
                    600: '#0E4F8C',
                    700: '#0B4478',
                    800: '#183247',
                    900: '#132B3B',
                },
                accent: {
                    50: '#F1FCFB',
                    100: '#DDF8F5',
                    500: '#16B8B0',
                    600: '#129C95',
                    700: '#0F7F7A',
                    800: '#0D6662',
                    900: '#0B4F4C',
                },
                surface: {
                    50: '#FFFFFF',
                    100: '#F7FAFC',
                    200: '#EEF4F7',
                },
                success: {
                    50: '#EDF9F4',
                    500: '#18A874',
                    600: '#12865C',
                },
                danger: {
                    50: '#FEF1F4',
                    500: '#D6455D',
                    600: '#B9354C',
                },
                warning: {
                    50: '#FFF7E8',
                    500: '#D99A1A',
                    600: '#B67F15',
                },
                info: {
                    50: '#EEF5FD',
                    500: '#1670B8',
                    600: '#0E4F8C',
                },
            },
            backgroundImage: {
                'brand-gradient': 'linear-gradient(135deg, #0E4F8C 0%, #18C2B7 100%)',
            },
            boxShadow: {
                'brand-soft': '0 18px 50px -24px rgba(14, 79, 140, 0.28)',
            },
        },
    },
    plugins: [forms],
};
