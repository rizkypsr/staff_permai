<script setup>
import { NavBar, Cell, CellGroup, Button, Icon } from 'vant'
import { computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengiriman: Object,
})

const handlePengembalian = () => {
    // TODO: Navigate to pengembalian page
    console.log('Pengembalian clicked')
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text)
}
</script>

<template>
    <AppLayout>
        <div class="sticky top-0 z-10 bg-white">
            <NavBar :title="pengiriman.no_transaksi" left-arrow @click-left="$inertia.visit('/pengiriman')" />
        </div>

        <div class="pb-24 bg-gray-50">
            <!-- Info Section -->
            <div class="bg-white p-4 mb-3">
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">Tgl. Kirim</div>
                    <div class="text-sm font-medium text-right">{{ pengiriman.tgl_formatted }}</div>
                </div>
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">No. Nota</div>
                    <div class="text-sm font-medium text-right">{{ pengiriman.no_nota }}</div>
                </div>
                <div class="flex justify-between items-start mb-2">
                    <div class="text-sm text-gray-600">Pelanggan</div>
                    <div class="text-sm font-medium text-right">{{ pengiriman.pelanggan }}</div>
                </div>
                <div class="flex justify-between items-start">
                    <div class="text-sm text-gray-600">No. Tlp/HP</div>
                    <div class="flex items-center gap-2">
                        <div class="text-sm font-medium">{{ pengiriman.no_telp || '-' }}</div>
                        <Icon v-if="pengiriman.no_telp" name="copy" size="16" class="text-gray-500 cursor-pointer" 
                            @click="copyToClipboard(pengiriman.no_telp)" />
                    </div>
                </div>
            </div>

            <!-- Alamat Pengiriman -->
            <div class="bg-white p-4 mb-3">
                <div class="text-sm font-semibold text-gray-900 mb-2">Alamat Pengiriman</div>
                <div class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
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

        <!-- Bottom Button -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white border-t border-gray-200 p-4">
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
