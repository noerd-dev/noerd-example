import defaultTheme from 'tailwindcss/defaultTheme'
import forms from '@tailwindcss/forms'

require('dotenv').config();

/** @type {import('tailwindcss').Config} */
export default {
    theme: {
        extend: {
            display: ['group-hover'],
            colors: {
                'brand-bg': '#f9f9f9', // Page background, table row hover
                'brand-navi': process.env.VITE_BRAND_NAVI || '#fafafa', // Sidebar background
                'brand-navi-hover': process.env.VITE_BRAND_NAVI_HOVER || '#f5f5f5', // Sidebar item hover
                'brand-primary': process.env.VITE_BRAND_PRIMARY || '#000', // Primary buttons, active indicators, checkbox bg
                'brand-primary-text': process.env.VITE_BRAND_PRIMARY_TEXT || '#fff', // Text on primary buttons, checkbox checkmark
                'brand-secondary': process.env.VITE_BRAND_SECONDARY || '#ffffff', // Secondary button background
                'brand-secondary-text': process.env.VITE_BRAND_SECONDARY_TEXT || '#374151', // Text on secondary buttons
                'brand-danger': process.env.VITE_BRAND_DANGER || '#fecaca', // Danger button background
                'brand-danger-text': process.env.VITE_BRAND_DANGER_TEXT || '#374151', // Text on danger buttons
                'brand-border': process.env.VITE_BRAND_BORDER || '#000', // Focus ring, active nav border
            },
        },
    },

    plugins: [forms, require('tailwind-scrollbar')],
}
