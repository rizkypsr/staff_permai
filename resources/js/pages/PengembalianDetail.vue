<script setup>
import { NavBar, Cell, CellGroup, Tag, showToast } from 'vant'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengembalian: Object,
})

const goBack = () => {
    router.visit('/pengiriman')
}

const copyToClipboard = (text) => {
    // Check if clipboard API is available
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(text).then(() => {
            showToast({
                message: 'Berhasil disalin',
                type: 'success',
                wordBreak: 'break-word',
            })
        }).catch(() => {
            showToast({
                message: 'Gagal menyalin',
                type: 'fail',
                wordBreak: 'break-word',
            })
        })
    } else {
        // Fallback for older browsers or non-secure contexts
        try {
            const textArea = document.createElement('textarea')
            textArea.value = text
            textArea.style.position = 'fixed'
            textArea.style.left = '-999999px'
            textArea.style.top = '-999999px'
            document.body.appendChild(textArea)
            textArea.focus()
            textArea.select()
            document.execCommand('copy')
            textArea.remove()

            showToast({
                message: 'Berhasil disalin',
                type: 'success',
                wordBreak: 'break-word',
            })
        } catch (err) {
            showToast({
                message: 'Gagal menyalin',
                type: 'fail',
                wordBreak: 'break-word',
            })
        }
    }
}

const getStatusColor = (isApprove) => {
    switch (isApprove) {
        case 0: return 'warning'
        case 1: return 'success'
        case 2: return 'danger'
        default: return 'default'
    }
}

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num)
}
</script>

<template>
    <AppLayout>
        <div class="h-dvh flex flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white flex-shrink-0">
                <NavBar :title="pengembalian.no_transaksi" left-arrow @click-left="goBack" />
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="pb-4">
            <!-- Info Section -->
            <div class="bg-white p-4 mb-3">
                <div class="flex justify-between items-start mb-3">
                    <div class="text-sm text-gray-600">Status</div>
                    <Tag :type="getStatusColor(pengembalian.is_approve)" size="medium">
                        {{ pengembalian.status_text }}
                    </Tag>
                </div>
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">Tgl. Pengembalian</div>
                    <div class="text-sm font-medium text-right">{{ pengembalian.tgl_formatted }}</div>
                </div>
                <div class="flex justify-between items-start" :class="{ 'mb-2': pengembalian.keterangan }">
                    <div class="text-sm text-gray-600">No. Pengiriman</div>
                    <div class="text-sm font-medium text-right">
                        <span class="underline cursor-pointer hover:text-blue-600"
                            @click="copyToClipboard(pengembalian.no_pengiriman)">
                            {{ pengembalian.no_pengiriman }}
                        </span>
                    </div>
                </div>
                <div v-if="pengembalian.keterangan" class="flex justify-between items-start">
                    <div class="text-sm text-gray-600">Keterangan</div>
                    <div class="text-sm font-medium text-right max-w-48 text-right">{{ pengembalian.keterangan }}</div>
                </div>
            </div>

            <!-- Detail Produk Pengembalian -->
            <div class="bg-white p-4 mb-3">
                <div class="text-sm font-semibold text-gray-900 mb-3">Detail Produk Pengembalian</div>
                <div v-if="pengembalian.detail.length > 0" class="space-y-3">
                    <div v-for="item in pengembalian.detail" :key="item.id" class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm font-medium text-gray-900 mb-3">{{ item.produk }}</div>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Qty Dibawa:</span>
                                <p class="font-bold text-gray-900">{{ formatNumber(item.qty_bawa) }} {{ item.satuan }}
                                </p>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Qty Dikembalikan:</span>
                                <p class="font-bold text-gray-900">{{ formatNumber(item.qty_kembali) }} {{ item.satuan
                                    }}</p>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Qty Dipakai:</span>
                                <p class="font-bold text-gray-900">{{ formatNumber(item.qty_dipakai) }} {{ item.satuan
                                    }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500 text-center py-4">
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