<script setup>
import { router } from '@inertiajs/vue3'
import { InfiniteScroll } from '@inertiajs/vue3'
import { NavBar, Button, Search, Empty, List, PullRefresh, Icon, RadioGroup, Radio } from 'vant'
import { ref, watch, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengiriman: Object,
    search: String,
    is_approve: String,
})

const searchQuery = ref(props.search || '')
const approvalFilter = ref(props.is_approve || '')
const refreshing = ref(false)

const handleAddPengiriman = () => {
    router.visit('/pengiriman/create')
}

const getKaryawanNames = (persons) => {
    return persons.map(p => p.nama).join(', ')
}

const handleDetailClick = (id) => {
    router.visit(`/pengiriman/${id}`)
}

// Handle search
const handleSearch = () => {
    if (searchQuery.value === props.search && approvalFilter.value === props.is_approve) return

    const url = new URL(window.location.href)
    if (searchQuery.value) {
        url.searchParams.set('search', searchQuery.value)
    } else {
        url.searchParams.delete('search')
    }
    
    if (approvalFilter.value !== '') {
        url.searchParams.set('is_approve', approvalFilter.value)
    } else {
        url.searchParams.delete('is_approve')
    }

    router.visit(url.toString(), {
        preserveState: false,
        preserveScroll: false,
    })
}

// Handle approval filter change
const handleApprovalFilter = (value) => {
    approvalFilter.value = value
    handleSearch()
}

// Handle pull to refresh
const onRefresh = () => {
    refreshing.value = true

    const url = new URL(window.location.href)
    url.searchParams.delete('page') // Reset to first page

    router.visit(url.toString(), {
        preserveState: false,
        preserveScroll: false,
        onFinish: () => {
            refreshing.value = false
        }
    })
}

// Watch search query changes
watch(searchQuery, (newValue) => {
    if (newValue === '') {
        handleSearch()
    }
})

// Watch approval filter changes
watch(approvalFilter, (newValue) => {
    if (newValue !== props.is_approve) {
        handleSearch()
    }
})

// Check if we have data
const hasData = computed(() => {
    return props.pengiriman?.data && props.pengiriman.data.length > 0
})

// Check if finished loading (no more pages)
const finished = computed(() => {
    return !props.pengiriman?.next_page_url
})
</script>

<template>
    <AppLayout>
        <!-- Header/Navbar -->
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Pengiriman" />

            <!-- Search Bar and Filter -->
            <div class="px-4 pt-3 pb-6 space-y-3">
                <!-- Search and Add Button -->
                <div class="flex gap-2 items-center">
                    <Search v-model="searchQuery" placeholder="Cari Pengiriman" shape="round" class="flex-1"
                        @search="handleSearch" />
                    <Button icon="plus" size="small" type="primary" round class="black-icon" @click="handleAddPengiriman" />
                </div>
                
                <!-- Filter Radio -->
                <div class="space-y-2 pb-2">
                    <span class="text-sm text-gray-600">Filter Status:</span>
                    <RadioGroup v-model="approvalFilter" direction="horizontal" @change="handleApprovalFilter">
                        <Radio name="" checked-color="#fec109">Semua</Radio>
                        <Radio name="0" checked-color="#fec109">Belum Disetujui</Radio>
                        <Radio name="1" checked-color="#fec109">Disetujui</Radio>
                    </RadioGroup>
                </div>
            </div>
        </div>

        <!-- Main Content with Pull Refresh and Infinite Scroll -->
        <PullRefresh v-model="refreshing" @refresh="onRefresh">
            <InfiniteScroll data="pengiriman" v-slot="{ loading }">
                <List loading :finished="finished" finished-text="Tidak ada data lagi">
                    <div class="p-4">
                        <!-- List Pengiriman -->
                        <div v-if="hasData" class="space-y-3">
                            <div v-for="item in pengiriman.data" :key="item.id"
                                class="bg-white rounded-lg overflow-hidden shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                                @click="handleDetailClick(item.id)">
                                <!-- Header with dynamic color based on approval status -->
                                <div :class="item.is_approve === 1 ? 'bg-green-500' : 'bg-[#fec109]'" class="text-black px-4 py-3 font-semibold text-center">
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
                                    <!-- Status Pengembalian - hanya tampil jika pengambilan_pipa null -->
                                    <div v-if="item.pengambilan_pipa === null" class="flex justify-between">
                                        <span class="text-gray-600">Status</span>
                                        <span class="text-orange-600 font-medium">Belum Dikembalikan</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="mt-8">
                            <Empty
                                :description="searchQuery ? 'Tidak ada hasil pencarian' : 'Belum ada data pengiriman'" />
                        </div>
                    </div>
                </List>
            </InfiniteScroll>
        </PullRefresh>
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

:deep(.van-list__finished-text) {
    color: #969799;
    font-size: 12px;
    text-align: center;
    padding: 16px 0;
}

:deep(.van-pull-refresh__track) {
    min-height: 100%;
}

/* Black icon for add button */
:deep(.black-icon .van-icon) {
    color: #000000 !important;
}
</style>