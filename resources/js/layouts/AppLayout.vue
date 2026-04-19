<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Tabbar, TabbarItem } from 'vant'

const props = defineProps({
    auth: Object,
})

// Get current route name from URL
const getCurrentRoute = () => {
    if (typeof window === 'undefined') return 'absensi'
    
    const path = window.location.pathname
    if (path === '/' || path === '/absensi') return 'absensi'
    if (path === '/pengiriman') return 'pengiriman'
    if (path === '/stok') return 'stok'
    return 'absensi'
}

// Initialize with server-safe defaults
const shouldShowTabbar = ref(false)
const active = ref('absensi')
const isWelcomePage = ref(false)

onMounted(() => {
    // Only access window after component is mounted
    if (typeof window !== 'undefined') {
        active.value = getCurrentRoute()
        
        const path = window.location.pathname
        shouldShowTabbar.value = path === '/' || path === '/pengiriman' || path === '/stok'
        isWelcomePage.value = path === '/' || path === '/absensi'
    }
})

const onChange = (name) => {
    active.value = name
    const routes = {
        absensi: '/',
        pengiriman: '/pengiriman',
        stok: '/stok'
    }
    router.visit(routes[name])
}
</script>

<template>
    <div class="h-dvh bg-gray-50 flex justify-center overflow-hidden">
        <!-- Mobile-first container with max-width for desktop -->
        <div class="w-full max-w-md bg-white h-full shadow-lg relative flex flex-col" :class="{ 'overflow-hidden': isWelcomePage }">
            <!-- Main Content - conditional scroll behavior -->
            <div class="flex-1" :class="{ 'overflow-hidden': isWelcomePage, 'overflow-y-auto': !isWelcomePage }">
                <slot />
            </div>

            <!-- Bottom Tabbar - only show on main pages -->
            <div v-if="shouldShowTabbar" class="flex-shrink-0 bg-white border-t border-gray-200">
                <Tabbar
                    v-model="active"
                    active-color="#fec109"
                    inactive-color="#7d7d7d"
                    @change="onChange"
                    :fixed="false"
                >
                    <TabbarItem name="absensi" icon="clock-o">
                        Absensi
                    </TabbarItem>
                    <TabbarItem name="pengiriman" icon="logistics">
                        Pengiriman
                    </TabbarItem>
                    <TabbarItem name="stok" icon="records">
                        Stok
                    </TabbarItem>
                </Tabbar>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Increase tabbar font and icon size */
:deep(.van-tabbar-item__text) {
    font-size: 14px;
    font-weight: 500;
}

:deep(.van-tabbar-item__icon) {
    font-size: 24px;
    margin-bottom: 4px;
}

:deep(.van-tabbar-item) {
    padding: 8px 0;
}
</style>
