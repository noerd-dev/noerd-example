import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

require('dotenv').config();

/** @type {import('tailwindcss').Config} */
export default {
    content: [

    ],

    safelist: [

    ],

    theme: {
        extend: {
            display: ['group-hover'],
            colors: {
                'brand-bg': process.env.VITE_BG_COLOR || '#f9f9f9',
                'brand-navi': process.env.VITE_BRAND_NAVI || '#fafafa',
                'brand-navi-hover': process.env.VITE_BRAND_NAVI_HOVER || '#f5f5f5',
                'brand-primary': process.env.VITE_BRAND_PRIMARY || '#000',
                'brand-border': process.env.VITE_BRAND_BORDER || '#000',
            },
        },
    },

    plugins: [forms, require('tailwind-scrollbar')],
}
