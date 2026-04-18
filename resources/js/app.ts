import { createInertiaApp } from '@inertiajs/vue3';
import { Locale } from 'vant';
import idId from 'vant/es/locale/lang/id-ID';

import 'vant/lib/index.css';

// Set Vant locale to English
Locale.use('id-ID', idId);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize font size from localStorage
const initializeFontSize = () => {
    const savedFontSize = localStorage.getItem('app-font-size')
    if (savedFontSize) {
        // Font size options with very subtle scales
        const fontSizeOptions: Record<string, number> = {
            'small': 0.97,
            'normal': 1.0,
            'medium': 1.03,
            'large': 1.06,
        }
        
        const scale = fontSizeOptions[savedFontSize] || 1.0
        // Apply font scaling using CSS custom property
        document.documentElement.style.setProperty('--font-scale', scale.toString())
        
        // Add CSS class to enable font scaling
        document.documentElement.classList.add('font-scaling-enabled')
    }
}

// Initialize font size when DOM is ready
if (typeof window !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFontSize)
    } else {
        initializeFontSize()
    }
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#fec109',
    },
}).catch(console.error);
