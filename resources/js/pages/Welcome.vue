<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { NavBar, Icon, Button, Tab, Tabs, Cell, CellGroup, Empty, showToast, showConfirmDialog, Popover } from 'vant'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    absensiData: Array,
    todayAbsensi: Object,
    canAbsen: Boolean,
})

// Current time - initialize with server-safe value
const currentTime = ref(new Date())
const currentDate = computed(() => {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
    return currentTime.value.toLocaleDateString('id-ID', options)
})
const currentTimeString = computed(() => {
    return currentTime.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
})

// Update time every second
let timeInterval = null
onMounted(() => {
    // Only start interval after component is mounted
    timeInterval = setInterval(() => {
        currentTime.value = new Date()
    }, 1000)
})

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval)
        timeInterval = null
    }
})

// Tabs - set active to last tab (current month)
const activeTab = ref(props.absensiData.length - 1)

// Popover menu
const showPopover = ref(false)
const menuActions = [
    { text: 'Pengaturan', icon: 'setting-o' },
    { text: 'Logout', icon: 'sign' },
]

// Today's data
const jamMasuk = computed(() => props.todayAbsensi?.masuk || '-')
const status = computed(() => props.todayAbsensi?.status_text || 'BELUM ABSEN')

const handleAbsen = async () => {
    if (!props.canAbsen) {
        if (props.todayAbsensi) {
            showToast({
                message: 'Anda sudah absen hari ini',
                type: 'fail',
                wordBreak: 'break-word',
            })
        } else {
            showToast({
                message: 'Absen hanya bisa dilakukan mulai jam 06:00',
                type: 'fail',
                wordBreak: 'break-word',
            })
        }
        return
    }

    try {
        await showConfirmDialog({
            title: 'Konfirmasi Absen',
            message: 'Apakah Anda yakin ingin melakukan absen sekarang?',
            confirmButtonText: 'Ya, Absen',
            cancelButtonText: 'Batal',
        })

        router.post('/absensi', {}, {
            onSuccess: () => {
                showToast({
                    message: 'Absen berhasil dicatat',
                    type: 'success',
                    wordBreak: 'break-word',
                })
            },
            onError: (errors) => {
                showToast({
                    message: errors.error || 'Terjadi kesalahan',
                    type: 'fail',
                    wordBreak: 'break-word',
                })
            },
        })
    } catch (error) {
        // User cancelled
    }
}

const handleMenuClick = (action) => {
    showPopover.value = false

    if (action.text === 'Pengaturan') {
        router.visit('/settings')
    } else if (action.text === 'Logout') {
        handleLogout()
    }
}

const handleLogout = async () => {
    try {
        await showConfirmDialog({
            title: 'Konfirmasi Logout',
            message: 'Apakah Anda yakin ingin keluar?',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
        })

        router.post('/logout', {}, {
            onSuccess: () => {
                showToast({
                    message: 'Berhasil logout',
                    type: 'success',
                    wordBreak: 'break-word',
                })
            },
        })
    } catch (error) {
        // User cancelled
    }
}
</script>

<template>
    <AppLayout>
        <!-- Header with orange background -->
        <div class="bg-[#ff6b35] text-white">
            <!-- Navbar -->
            <NavBar title="MPE APP" class="!bg-transparent">
                <template #right>
                    <Popover v-model:show="showPopover" :actions="menuActions" placement="bottom-end"
                        @select="handleMenuClick">
                        <template #reference>
                            <Icon name="wap-nav" size="24" color="white" />
                        </template>
                    </Popover>
                </template>
            </NavBar>

            <!-- Time Display -->
            <div class="text-center py-8 px-4">
                <div class="font-bold mb-2" style="font-size: 4rem !important; line-height: 1 !important;">
                    {{ currentTimeString }}
                </div>
                <div class="opacity-90" style="font-size: 1.25rem !important;">
                    {{ currentDate }}
                </div>
            </div>

            <!-- Status Card -->
            <div class="px-4 pb-6">
                <div class="bg-white rounded-2xl p-6 text-gray-900">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-lg font-bold mb-1">Jam Masuk</div>
                            <div class="text-2xl font-bold">{{ jamMasuk }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold mb-1">Status</div>
                            <div class="text-sm font-semibold">{{ status }}</div>
                        </div>
                    </div>

                    <!-- Absen Button -->
                    <Button type="default" block round size="large" :disabled="!canAbsen"
                        class="!bg-black !text-white !border-black !h-12 !text-lg !font-bold disabled:!bg-gray-400 disabled:!border-gray-400"
                        @click="handleAbsen">
                        {{ canAbsen ? 'Absen' : (todayAbsensi ? 'Sudah Absen' : 'Belum Waktunya') }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Tabs for months -->
        <div class="bg-white">
            <Tabs v-model:active="activeTab" color="#fec109" title-active-color="#fec109"
                title-inactive-color="#969799">
                <Tab v-for="(monthData, index) in absensiData" :key="index" :title="monthData.month">
                    <div v-if="monthData.data.length > 0">
                        <CellGroup inset>
                            <Cell v-for="absen in monthData.data" :key="absen.id" :title="absen.tgl_formatted"
                                :label="`Masuk: ${absen.masuk || '-'}`">
                                <template #value>
                                    <div class="text-xs" :class="{
                                        'text-orange-600': absen.status === 0,
                                        'text-green-600': absen.status === 1,
                                        'text-yellow-600': absen.status === 2,
                                        'text-red-600': absen.status === 3,
                                    }">
                                        {{ absen.status_text }}
                                    </div>
                                </template>
                            </Cell>
                        </CellGroup>
                    </div>
                    <div v-else class="p-4">
                        <Empty :description="`Tidak ada data absensi untuk ${monthData.month}`" />
                    </div>
                </Tab>
            </Tabs>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.van-nav-bar) {
    background-color: transparent;
}

:deep(.van-nav-bar__title) {
    color: white;
    font-weight: 700;
    font-size: 20px;
}

:deep(.van-tabs__nav) {
    background-color: white;
}

:deep(.van-tab) {
    font-weight: 500;
}

:deep(.van-tab--active) {
    font-weight: 600;
}
</style>
