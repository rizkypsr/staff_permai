<script setup>
import { router, InfiniteScroll } from '@inertiajs/vue3'
import { NavBar, List, PullRefresh, Cell, Empty, Search } from 'vant'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    stok: Object,
    search: String,
})

const refreshing = ref(false)
const searchValue = ref(props.search || '')

const onRefresh = () => {
    refreshing.value = true
    router.reload({
        preserveState: false,
        preserveScroll: false,
        onFinish: () => {
            refreshing.value = false
        },
    })
}

const onSearch = () => {
    router.get('/stok', { search: searchValue.value }, {
        preserveState: false,
        preserveScroll: false,
        replace: true,
    })
}

const formatNumber = (num) => {
    return new Intl.NumberFormat('id-ID').format(num)
}

const hasData = computed(() => {
    return props.stok?.data && props.stok.data.length > 0
})

const finished = computed(() => {
    return !props.stok?.next_page_url
})
</script>

<template>
    <AppLayout>
        <!-- Header/Navbar -->
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Stok Produk" />
            <div class="px-4 pb-3">
                <Search v-model="searchValue" placeholder="Cari produk..." shape="round" @search="onSearch"
                    @clear="onSearch" />
            </div>
        </div>

        <!-- Main Content -->
        <PullRefresh v-model="refreshing" @refresh="onRefresh">
            <InfiniteScroll data="stok" v-slot="{ loading }">
                <List :loading="loading" :finished="finished" finished-text="Tidak ada data lagi">
                    <div v-if="hasData">
                        <Cell v-for="item in stok.data" :key="item.id" :title="item.nama" :label="item.kode" 
                              label-class="text-xs text-gray-500">
                            <template #value>
                                <div class="text-right stock-value">
                                    {{ formatNumber(item.qty) }} {{ item.satuan }}
                                </div>
                            </template>
                        </Cell>
                    </div>
                    <Empty v-else description="Tidak ada data stok" />
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

:deep(.van-cell__title) {
    font-size: 15px;
    font-weight: 500;
}

:deep(.van-cell__label) {
    font-size: 13px;
    margin-top: 4px;
}

.stock-value {
    font-size: 13px;
    font-weight: 500;
    color: #323233;
}
</style>
