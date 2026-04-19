<script setup>
import { NavBar, Cell, CellGroup, Button, Icon, showToast } from 'vant'
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengiriman: Object,
})

const handlePengembalian = () => {
    router.visit('/pengembalian/create')
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
</script>

<template>
    <AppLayout>
        <div class="sticky top-0 z-10 bg-white">
            <NavBar :title="pengiriman.no_transaksi" left-arrow @click-left="$inertia.visit('/pengiriman')" />
        </div>

        <div :class="pengiriman.can_return ? 'pb-24' : 'pb-4'" class="bg-gray-50">
            <!-- Info Section -->
            <div class="bg-white p-4 mb-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">Tgl. Kirim</div>
                    <div class="text-sm font-medium text-right">{{ pengiriman.tgl_formatted }}</div>
                </div>
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">No. Nota</div>
                    <div class="text-sm font-medium text-right">
                        <span class="underline cursor-pointer hover:text-blue-600"
                            @click="copyToClipboard(pengiriman.no_nota)">
                            {{ pengiriman.no_nota }}
                        </span>
                    </div>
                </div>
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">Pelanggan</div>
                    <div class="text-sm font-medium text-right">{{ pengiriman.pelanggan }}</div>
                </div>
                <div class="flex justify-between items-start">
                    <div class="text-sm text-gray-600">No. Tlp/HP</div>
                    <div class="text-sm font-medium text-right">
                        <span v-if="pengiriman.no_telp && pengiriman.no_telp !== '-'"
                            class="underline cursor-pointer hover:text-blue-600"
                            @click="copyToClipboard(pengiriman.no_telp)">
                            {{ pengiriman.no_telp }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>
            </div>

            <!-- Alamat Pengiriman -->
            <div class="bg-white p-4 mb-3">
                <div class="text-sm font-semibold text-gray-900 mb-2">Alamat Pengiriman</div>
                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded underline cursor-pointer hover:text-blue-600"
                    @click="copyToClipboard(pengiriman.alamat)">
                    {{ pengiriman.alamat }}
                </div>
            </div>

            <!-- Keterangan -->
            <div v-if="pengiriman.keterangan" class="bg-white p-4 mb-3">
                <div class="text-sm font-semibold text-gray-900 mb-2">Keterangan</div>
                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                    {{ pengiriman.keterangan }}
                </div>
            </div>

            <!-- Produk Nota -->
            <div class="bg-white p-4 mb-3">
                <div class="flex justify-between items-center mb-3">
                    <div class="text-sm font-semibold text-gray-900">Produk Nota</div>
                    <div class="text-xs text-gray-500">Qty</div>
                </div>
                <div v-if="pengiriman.produk_nota.length > 0" class="space-y-2">
                    <div v-for="item in pengiriman.produk_nota" :key="item.id" 
                        class="flex justify-between items-start py-2 border-b border-gray-100 last:border-0">
                        <div class="flex-1 pr-4">
                            <div class="text-sm text-gray-900">{{ item.uraian }}</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ item.qty }} {{ item.satuan }}
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500 text-center py-4">
                    Tidak ada produk nota
                </div>
            </div>

            <!-- Produk Pipa -->
            <div class="bg-white p-4 mb-3">
                <div class="flex justify-between items-center mb-3">
                    <div class="text-sm font-semibold text-gray-900">Produk Pipa</div>
                    <div class="text-xs text-gray-500">Qty</div>
                </div>
                <div v-if="pengiriman.produk_pipa.length > 0" class="space-y-2">
                    <div v-for="item in pengiriman.produk_pipa" :key="item.id" 
                        class="flex justify-between items-start py-2 border-b border-gray-100 last:border-0">
                        <div class="flex-1 pr-4">
                            <div class="text-sm text-gray-900">{{ item.uraian }}</div>
                        </div>
                        <div class="text-sm font-medium text-gray-900 whitespace-nowrap">
                            {{ item.qty }} {{ item.satuan }}
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500 text-center py-4">
                    Tidak ada produk pipa
                </div>
            </div>
        </div>

        <!-- Bottom Button - Fixed at bottom like tabbar -->
        <div v-if="pengiriman.can_return" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white border-t border-gray-200 p-4">
            <Button type="primary" block round size="large" @click="handlePengembalian">
                Pengembalian
            </Button>
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
