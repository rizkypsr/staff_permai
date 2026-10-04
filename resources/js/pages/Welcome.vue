<script setup>
import { router } from '@inertiajs/vue3';
import {
    NavBar,
    Icon,
    Button,
    Tab,
    Tabs,
    Cell,
    CellGroup,
    Empty,
    showToast,
    showConfirmDialog,
    Popover,
} from 'vant';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as utangIndex } from '@/routes/utang';

const props = defineProps({
    auth: Object,
    absensiData: Array,
    todayAbsensi: Object,
    canAbsen: Boolean,
});

// Current time - initialize with server-safe value
const currentTime = ref(new Date());
const currentDate = computed(() => {
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };

    return currentTime.value.toLocaleDateString('id-ID', options);
});
const currentTimeString = computed(() => {
    return currentTime.value.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

// Update time every second
let timeInterval = null;
onMounted(() => {
    // Only start interval after component is mounted
    timeInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
        timeInterval = null;
    }
});

// Tabs - set active to last tab (current month)
const activeTab = ref(props.absensiData.length - 1);

// Popover menu
const showPopover = ref(false);
const menuActions = [
    { text: 'Profile', icon: 'user-o' },
    { text: 'Rekap Absensi', icon: 'calendar-o' },
    { text: 'Utang', icon: 'balance-o' },
    { text: 'Pengaturan', icon: 'setting-o' },
    { text: 'Logout', icon: 'sign' },
];

// Today's data
const jamMasuk = computed(() => props.todayAbsensi?.masuk || '-');
const status = computed(() => props.todayAbsensi?.status_text || 'BELUM ABSEN');

const handleAbsen = async () => {
    if (!props.canAbsen) {
        if (props.todayAbsensi) {
            showToast({
                message: 'Anda sudah absen hari ini',
                type: 'fail',
                wordBreak: 'break-word',
            });
        } else {
            showToast({
                message: 'Absen hanya bisa dilakukan mulai jam 06:00',
                type: 'fail',
                wordBreak: 'break-word',
            });
        }

        return;
    }

    try {
        await showConfirmDialog({
            title: 'Konfirmasi Absen',
            message: 'Apakah Anda yakin ingin melakukan absen sekarang?',
            confirmButtonText: 'Ya, Absen',
            cancelButtonText: 'Batal',
        });

        router.post(
            '/absensi',
            {},
            {
                onSuccess: () => {
                    showToast({
                        message: 'Absen berhasil dicatat',
                        type: 'success',
                        wordBreak: 'break-word',
                    });
                },
                onError: (errors) => {
                    showToast({
                        message: errors.error || 'Terjadi kesalahan',
                        type: 'fail',
                        wordBreak: 'break-word',
                    });
                },
            },
        );
    } catch {
        // User cancelled
    }
};

const handleMenuClick = (action) => {
    showPopover.value = false;

    if (action.text === 'Profile') {
        router.visit('/profile');
    } else if (action.text === 'Rekap Absensi') {
        router.visit('/rekap-absensi');
    } else if (action.text === 'Utang') {
        router.visit(utangIndex().url);
    } else if (action.text === 'Pengaturan') {
        router.visit('/settings');
    } else if (action.text === 'Logout') {
        handleLogout();
    }
};

const handleLogout = async () => {
    try {
        await showConfirmDialog({
            title: 'Konfirmasi Logout',
            message: 'Apakah Anda yakin ingin keluar?',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
        });

        router.post(
            '/logout',
            {},
            {
                onSuccess: () => {
                    showToast({
                        message: 'Berhasil logout',
                        type: 'success',
                        wordBreak: 'break-word',
                    });
                },
            },
        );
    } catch {
        // User cancelled
    }
};
</script>

<template>
    <AppLayout>
        <div class="flex h-dvh flex-col">
            <!-- Header with orange background - Fixed -->
            <div class="shrink-0 bg-[#ff6b35] text-white">
                <!-- Navbar -->
                <NavBar title="MPE APP" class="bg-transparent!">
                    <template #right>
                        <Popover
                            v-model:show="showPopover"
                            :actions="menuActions"
                            placement="bottom-end"
                            @select="handleMenuClick"
                        >
                            <template #reference>
                                <Icon name="wap-nav" size="24" color="white" />
                            </template>
                        </Popover>
                    </template>
                </NavBar>

                <!-- Time Display -->
                <div class="px-4 py-4 text-center">
                    <div
                        class="mb-2 font-bold"
                        style="
                            font-size: 2.5rem !important;
                            line-height: 1 !important;
                        "
                    >
                        {{ currentTimeString }}
                    </div>
                    <div
                        class="opacity-90"
                        style="font-size: 1.1rem !important"
                    >
                        {{ currentDate }}
                    </div>
                </div>

                <!-- Status Card -->
                <div class="px-4 pb-2">
                    <div class="rounded-2xl bg-white px-6 py-4 text-gray-900">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <div class="mb-1 text-lg font-bold">
                                    Jam Masuk
                                </div>
                                <div class="text-2xl font-bold">
                                    {{ jamMasuk }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="mb-1 text-lg font-bold">Status</div>
                                <div class="text-sm font-semibold">
                                    {{ status }}
                                </div>
                            </div>
                        </div>

                        <!-- Absen Button -->
                        <Button
                            type="default"
                            block
                            round
                            size="large"
                            :disabled="!canAbsen"
                            class="h-12! border-black! bg-black! text-lg! font-bold! text-white! disabled:border-gray-400! disabled:bg-gray-400!"
                            @click="handleAbsen"
                        >
                            {{
                                canAbsen
                                    ? 'Absen'
                                    : todayAbsensi
                                      ? 'Sudah Absen'
                                      : 'Belum Waktunya'
                            }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabs for months - Scrollable area -->
            <div class="flex flex-1 flex-col overflow-hidden bg-white">
                <Tabs
                    v-model:active="activeTab"
                    color="#fec109"
                    title-active-color="#fec109"
                    title-inactive-color="#969799"
                    class="flex h-full flex-col"
                >
                    <Tab
                        v-for="(monthData, index) in absensiData"
                        :key="index"
                        :title="monthData.month"
                        class="flex-1 overflow-hidden"
                    >
                        <!-- Scrollable content that fills remaining space -->
                        <div class="h-full overflow-y-auto">
                            <div v-if="monthData.data.length > 0">
                                <CellGroup inset>
                                    <Cell
                                        v-for="absen in monthData.data"
                                        :key="absen.id"
                                        :title="absen.tgl_formatted"
                                        :label="`Masuk: ${absen.masuk || '-'}`"
                                    >
                                        <template #value>
                                            <div
                                                class="text-xs"
                                                :class="{
                                                    'text-orange-600':
                                                        absen.status === 0,
                                                    'text-green-600':
                                                        absen.status === 1,
                                                    'text-yellow-600':
                                                        absen.status === 2,
                                                    'text-red-600':
                                                        absen.status === 3,
                                                }"
                                            >
                                                {{ absen.status_text }}
                                            </div>
                                        </template>
                                    </Cell>
                                </CellGroup>
                                <!-- Add padding at bottom so last item is fully visible -->
                                <div class="pb-20"></div>
                            </div>
                            <div v-else class="p-4">
                                <Empty
                                    :description="`Tidak ada data absensi untuk ${monthData.month}`"
                                />
                            </div>
                        </div>
                    </Tab>
                </Tabs>
            </div>
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

/* Ensure tabs content area fills available space */
:deep(.van-tabs__content) {
    flex: 1;
    overflow: hidden;
}

:deep(.van-tab__panel) {
    height: 100%;
    overflow: hidden;
}
</style>
