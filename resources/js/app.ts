import { createInertiaApp } from '@inertiajs/vue3';
import { Locale } from 'vant';
import idID from 'vant/es/locale/lang/id-ID';

import 'vant/lib/index.css';

// Set Vant locale to Indonesian
Locale.use('id-ID', idID);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize font size from localStorage
const initializeFontSize = () => {
    const savedFontSize = localStorage.getItem('app-font-size')
    if (savedFontSize) {
        // Font size options with updated scales
        const fontSizeOptions: Record<string, number> = {
            'small': 1.0,      // Kecil (baseline)
            'normal': 1.03,    // Normal 
            'medium': 1.06,    // Besar
            'large': 1.1,      // Lebih Besar
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
