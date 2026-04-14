import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import daisyui from 'daisyui';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, daisyui],

    daisyui: {
        themes: [
            {
                'workflow': {
                    'primary':           '#2563EB',
                    'primary-content':   '#ffffff',
                    'secondary':         '#64748B',
                    'secondary-content': '#ffffff',
                    'accent':            '#3B82F6',
                    'accent-content':    '#ffffff',
                    'neutral':           '#1E293B',
                    'neutral-content':   '#F8FAFC',
                    'base-100':          '#FFFFFF',
                    'base-200':          '#F1F5F9',
                    'base-300':          '#E2E8F0',
                    'base-content':      '#0F172A',
                    'info':              '#0EA5E9',
                    'info-content':      '#ffffff',
                    'success':           '#10B981',
                    'success-content':   '#ffffff',
                    'warning':           '#F59E0B',
                    'warning-content':   '#ffffff',
                    'error':             '#EF4444',
                    'error-content':     '#ffffff',

                    '--rounded-box':     '0.75rem',
                    '--rounded-btn':     '0.5rem',
                    '--rounded-badge':   '0.375rem',
                    '--animation-btn':   '0.15s',
                    '--animation-input': '0.15s',
                    '--btn-focus-scale': '0.98',
                    '--border-btn':      '1px',
                    '--tab-border':      '2px',
                    '--tab-radius':      '0.5rem',
                },
            },
        ],
        darkTheme: false,
        logs: false,
    },
};
