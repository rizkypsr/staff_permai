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

// Check if tabbar should be shown - use ref to avoid hydration mismatch
const shouldShowTabbar = ref(true)

const active = ref('absensi')

onMounted(() => {
    active.value = getCurrentRoute()
    
    // Update shouldShowTabbar after mount to avoid hydration mismatch
    const path = window.location.pathname
    shouldShowTabbar.value = path === '/' || path === '/pengiriman' || path === '/stok'
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
    <div class="min-h-screen bg-gray-50 flex justify-center">
        <!-- Mobile-first container with max-width for desktop -->
        <div class="w-full max-w-md bg-white min-h-screen shadow-lg relative">
            <!-- Main Content with conditional padding for tabbar -->
            <div :class="shouldShowTabbar ? 'pb-[50px]' : ''">
                <slot />
            </div>

            <!-- Bottom Tabbar - only show on main pages -->
            <div v-if="shouldShowTabbar" class="absolute bottom-0 left-0 right-0 bg-white border-t border-gray-200">
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
