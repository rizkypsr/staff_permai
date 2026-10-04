<script setup>
import { router } from '@inertiajs/vue3';
import { NavBar, Button, showToast } from 'vant';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps({
    auth: Object,
    pengiriman: Object,
});

console.log(props.pengiriman);

const handlePengembalian = () => {
    if (props.pengiriman.pengambilan_pipa === 0) {
        // Bisa buat pengambilan baru
        router.visit('/pengembalian/create');
    } else {
        // Sudah ada pengambilan, buka detail
        router.visit(`/pengembalian/${props.pengiriman.pengambilan_pipa}`);
    }
};

const pengambilanButtonText = computed(() => {
    if (props.pengiriman.pengambilan_pipa === 0) {
        return 'Pengembalian';
    } else {
        return 'Lihat Pengembalian';
    }
});

const showPengambilanButton = computed(() => {
    // Tampilkan tombol jika:
    // - pengambilan_pipa === 0 (eligible, belum ada pengembalian)
    // - pengambilan_pipa > 0 (eligible, sudah ada pengembalian)
    // Jangan tampilkan jika pengambilan_pipa === null (tidak eligible)
    return props.pengiriman.pengambilan_pipa !== null;
});

const copyToClipboard = (text) => {
    // Check if clipboard API is available
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard
            .writeText(text)
            .then(() => {
                showToast({
                    message: 'Berhasil disalin',
                    type: 'success',
                    wordBreak: 'break-word',
                });
            })
            .catch(() => {
                showToast({
                    message: 'Gagal menyalin',
                    type: 'fail',
                    wordBreak: 'break-word',
                });
            });
    } else {
        // Fallback for older browsers or non-secure contexts
        try {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            document.execCommand('copy');
            textArea.remove();

            showToast({
                message: 'Berhasil disalin',
                type: 'success',
                wordBreak: 'break-word',
            });
        } catch {
            showToast({
                message: 'Gagal menyalin',
                type: 'fail',
                wordBreak: 'break-word',
            });
        }
    }
};
</script>

<template>
    <AppLayout>
        <div class="flex h-dvh flex-col bg-gray-50">
            <div class="sticky top-0 z-10 flex-shrink-0 bg-white">
                <NavBar
                    :title="pengiriman.no_transaksi"
                    left-arrow
                    @click-left="$inertia.visit('/pengiriman')"
                />
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <div :class="showPengambilanButton ? 'pb-24' : 'pb-4'">
                    <!-- Info Section -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-2 flex items-start justify-between">
                            <div class="text-sm text-gray-600">Tgl. Kirim</div>
                            <div class="text-right text-sm font-medium">
                                {{ pengiriman.tgl_formatted }}
                            </div>
                        </div>
                        <div class="mb-2 flex items-start justify-between">
                            <div class="text-sm text-gray-600">No. Nota</div>
                            <div class="text-right text-sm font-medium">
                                <span
                                    class="cursor-pointer underline hover:text-blue-600"
                                    @click="copyToClipboard(pengiriman.no_nota)"
                                >
                                    {{ pengiriman.no_nota }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-2 flex items-start justify-between">
                            <div class="text-sm text-gray-600">Pelanggan</div>
                            <div class="text-right text-sm font-medium">
                                {{ pengiriman.pelanggan }}
                            </div>
                        </div>
                        <div class="flex items-start justify-between">
                            <div class="text-sm text-gray-600">No. Tlp/HP</div>
                            <div class="text-right text-sm font-medium">
                                <span
                                    v-if="
                                        pengiriman.no_telp &&
                                        pengiriman.no_telp !== '-'
                                    "
                                    class="cursor-pointer underline hover:text-blue-600"
                                    @click="copyToClipboard(pengiriman.no_telp)"
                                >
                                    {{ pengiriman.no_telp }}
                                </span>
                                <span v-else>-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alamat Pengiriman -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-2 text-sm font-semibold text-gray-900">
                            Alamat Pengiriman
                        </div>
                        <div
                            class="cursor-pointer rounded bg-gray-50 p-3 text-sm text-gray-700 underline hover:text-blue-600"
                            @click="copyToClipboard(pengiriman.alamat)"
                        >
                            {{ pengiriman.alamat }}
                        </div>
                    </div>

                    <!-- Keterangan -->
                    <div v-if="pengiriman.keterangan" class="mb-3 bg-white p-4">
                        <div class="mb-2 text-sm font-semibold text-gray-900">
                            Keterangan
                        </div>
                        <div
                            class="rounded bg-gray-50 p-3 text-sm text-gray-700"
                        >
                            {{ pengiriman.keterangan }}
                        </div>
                    </div>

                    <!-- Produk Nota -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-900">
                                Produk Nota
                            </div>
                            <div class="text-xs text-gray-500">Qty</div>
                        </div>
                        <div
                            v-if="pengiriman.produk_nota.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="item in pengiriman.produk_nota"
                                :key="item.id"
                                class="flex items-start justify-between border-b border-gray-100 py-2 last:border-0"
                            >
                                <div class="flex-1 pr-4">
                                    <div class="text-sm text-gray-900">
                                        {{ item.uraian }}
                                    </div>
                                </div>
                                <div
                                    class="text-sm font-medium whitespace-nowrap text-gray-900"
                                >
                                    {{ item.qty }} {{ item.satuan }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="py-4 text-center text-sm text-gray-500"
                        >
                            Tidak ada produk nota
                        </div>
                    </div>

                    <!-- Produk Pipa -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gray-900">
                                Produk Pipa
                            </div>
                            <div class="text-xs text-gray-500">Qty</div>
                        </div>
                        <div
                            v-if="pengiriman.produk_pipa.length > 0"
                            class="space-y-2"
                        >
                            <div
                                v-for="item in pengiriman.produk_pipa"
                                :key="item.id"
                                class="flex items-start justify-between border-b border-gray-100 py-2 last:border-0"
                            >
                                <div class="flex-1 pr-4">
                                    <div class="text-sm text-gray-900">
                                        {{ item.uraian }}
                                    </div>
                                </div>
                                <div
                                    class="text-sm font-medium whitespace-nowrap text-gray-900"
                                >
                                    {{ item.qty }} {{ item.satuan }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="py-4 text-center text-sm text-gray-500"
                        >
                            Tidak ada produk pipa
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Button - Fixed at bottom like tabbar -->
            <div
                v-if="showPengambilanButton"
                class="fixed bottom-0 left-1/2 w-full max-w-md flex-shrink-0 -translate-x-1/2 border-t border-gray-200 bg-white p-4"
            >
                <Button
                    type="primary"
                    block
                    round
                    size="large"
                    @click="handlePengembalian"
                >
                    {{ pengambilanButtonText }}
                </Button>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.van-nav-bar) {
    background-color: #ffffff;
}

:deep(.van-nav-bar__title) {
    font-weight: 600;
    color: #ff6b35;
}

:deep(.van-button--primary) {
    background-color: #fec109;
    border-color: #fec109;
}
</style>
