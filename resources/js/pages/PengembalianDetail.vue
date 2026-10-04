<script setup>
import { router } from '@inertiajs/vue3';
import { NavBar, Tag, showToast } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    auth: Object,
    pengembalian: Object,
});

const goBack = () => {
    router.visit('/pengiriman');
};

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

const getStatusColor = (isApprove) => {
    switch (isApprove) {
        case 0:
            return 'warning';
        case 1:
            return 'success';
        case 2:
            return 'danger';
        default:
            return 'default';
    }
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num);
};
</script>

<template>
    <AppLayout>
        <div class="flex h-dvh flex-col bg-gray-50">
            <div class="sticky top-0 z-10 flex-shrink-0 bg-white">
                <NavBar
                    :title="pengembalian.no_transaksi"
                    left-arrow
                    @click-left="goBack"
                />
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="pb-4">
                    <!-- Info Section -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-3 flex items-start justify-between">
                            <div class="text-sm text-gray-600">Status</div>
                            <Tag
                                :type="getStatusColor(pengembalian.is_approve)"
                                size="medium"
                            >
                                {{ pengembalian.status_text }}
                            </Tag>
                        </div>
                        <div class="mb-2 flex items-start justify-between">
                            <div class="text-sm text-gray-600">
                                Tgl. Pengembalian
                            </div>
                            <div class="text-right text-sm font-medium">
                                {{ pengembalian.tgl_formatted }}
                            </div>
                        </div>
                        <div
                            class="flex items-start justify-between"
                            :class="{ 'mb-2': pengembalian.keterangan }"
                        >
                            <div class="text-sm text-gray-600">
                                No. Pengiriman
                            </div>
                            <div class="text-right text-sm font-medium">
                                <span
                                    class="cursor-pointer underline hover:text-blue-600"
                                    @click="
                                        copyToClipboard(
                                            pengembalian.no_pengiriman,
                                        )
                                    "
                                >
                                    {{ pengembalian.no_pengiriman }}
                                </span>
                            </div>
                        </div>
                        <div
                            v-if="pengembalian.keterangan"
                            class="flex items-start justify-between"
                        >
                            <div class="text-sm text-gray-600">Keterangan</div>
                            <div
                                class="max-w-48 text-right text-sm font-medium"
                            >
                                {{ pengembalian.keterangan }}
                            </div>
                        </div>
                    </div>

                    <!-- Detail Produk Pengembalian -->
                    <div class="mb-3 bg-white p-4">
                        <div class="mb-3 text-sm font-semibold text-gray-900">
                            Detail Produk Pengembalian
                        </div>
                        <div
                            v-if="pengembalian.detail.length > 0"
                            class="space-y-3"
                        >
                            <div
                                v-for="item in pengembalian.detail"
                                :key="item.id"
                                class="rounded-lg bg-gray-50 p-3"
                            >
                                <div
                                    class="mb-3 text-sm font-medium text-gray-900"
                                >
                                    {{ item.produk }}
                                </div>
                                <div class="space-y-2 text-xs">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-gray-500"
                                            >Qty Dibawa:</span
                                        >
                                        <p class="font-bold text-gray-900">
                                            {{ formatNumber(item.qty_bawa) }}
                                            {{ item.satuan }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-gray-500"
                                            >Qty Dikembalikan:</span
                                        >
                                        <p class="font-bold text-gray-900">
                                            {{ formatNumber(item.qty_kembali) }}
                                            {{ item.satuan }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-gray-500"
                                            >Qty Dipakai:</span
                                        >
                                        <p class="font-bold text-gray-900">
                                            {{ formatNumber(item.qty_dipakai) }}
                                            {{ item.satuan }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="py-4 text-center text-sm text-gray-500"
                        >
                            Tidak ada detail produk
                        </div>
                    </div>
                </div>
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
</style>
