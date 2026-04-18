import { createInertiaApp } from '@inertiajs/vue3';
import { Locale } from 'vant';
import enUS from 'vant/es/locale/lang/id-ID';

import 'vant/lib/index.css';

// Set Vant locale to English
Locale.use('en-US', enUS);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#fec109',
    },
});
