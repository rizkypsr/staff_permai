<script setup>
import { router } from '@inertiajs/vue3'
import { NavBar, Button, Search, Empty } from 'vant'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengiriman: Array,
})

const searchQuery = ref('')

const filteredPengiriman = computed(() => {
    if (!searchQuery.value) {
        return props.pengiriman
    }

    const query = searchQuery.value.toLowerCase()
    return props.pengiriman.filter(item =>
        item.no_transaksi.toLowerCase().includes(query) ||
        item.pelanggan.toLowerCase().includes(query) ||
        item.persons.some(p => p.nama.toLowerCase().includes(query))
    )
})

const handleAddPengiriman = () => {
    router.visit('/pengiriman/create')
}

const getKaryawanNames = (persons) => {
    return persons.map(p => p.nama).join(', ')
}

const handleDetailClick = (id) => {
    router.visit(`/pengiriman/${id}`)
}
</script>

<template>
    <AppLayout>
        <!-- Header/Navbar -->
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Pengiriman" left-arrow @click-left="$inertia.visit('/')" />

            <!-- Search Bar -->
            <div class="px-4 py-3 flex gap-2 items-center">
                <Search v-model="searchQuery" placeholder="Cari Pengiriman" shape="round" class="flex-1" />
                <Button icon="plus" size="small" type="primary" round @click="handleAddPengiriman" />
            </div>
        </div>

        <!-- Main Content -->
        <div class="p-4 pb-20">
            <!-- List Pengiriman -->
            <div v-if="filteredPengiriman.length > 0" class="space-y-3">
                <div v-for="item in filteredPengiriman" :key="item.id"
                    class="bg-white rounded-lg overflow-hidden shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                    @click="handleDetailClick(item.id)">
                    <!-- Header Orange -->
                    <div class="bg-[#d97642] text-white px-4 py-3 font-semibold text-center">
                        {{ item.no_transaksi }}
                    </div>

                    <!-- Content -->
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tgl. Kirim</span>
                            <span class="text-gray-900">{{ item.tgl_formatted }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pelanggan</span>
                            <span class="text-gray-900 text-right">{{ item.pelanggan }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Karyawan</span>
                            <span class="text-gray-900 text-right">
                                {{ getKaryawanNames(item.persons) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="mt-8">
                <Empty :description="searchQuery ? 'Tidak ada hasil pencarian' : 'Belum ada data pengiriman'" />
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
}

:deep(.van-search) {
    padding: 0;
}

:deep(.van-search__content) {
    background-color: #f5f5f5;
}

:deep(.van-button--primary) {
    background-color: #fec109;
    border-color: #fec109;
}
</style>
