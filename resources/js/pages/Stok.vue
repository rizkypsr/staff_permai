<script setup>
import { Link } from '@inertiajs/vue3'
import { NavBar, Button, Grid, GridItem, Empty } from 'vant'
import AppLayout from '@/layouts/AppLayout.vue'

defineProps({
    auth: Object,
})
</script>

<template>
    <AppLayout>
        <!-- Header/Navbar - positioned within max-width container -->
        <div class="sticky top-0 z-10">
            <NavBar title="Stok">
                <template #right>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                    >
                        <Button size="small" type="danger" round>Logout</Button>
                    </Link>
                </template>
            </NavBar>
        </div>

        <!-- Main Content -->
        <div class="p-4">
            <!-- Quick Actions -->
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3 px-2">Menu Stok</h3>
                <Grid :column-num="2" :gutter="12">
                    <GridItem>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                            <div class="text-3xl mb-2">📊</div>
                            <div class="text-sm font-medium text-gray-900">Lihat Stok</div>
                        </div>
                    </GridItem>
                    <GridItem>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                            <div class="text-3xl mb-2">➕</div>
                            <div class="text-sm font-medium text-gray-900">Tambah Stok</div>
                        </div>
                    </GridItem>
                    <GridItem>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                            <div class="text-3xl mb-2">➖</div>
                            <div class="text-sm font-medium text-gray-900">Kurangi Stok</div>
                        </div>
                    </GridItem>
                    <GridItem>
                        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center">
                            <div class="text-3xl mb-2">📋</div>
                            <div class="text-sm font-medium text-gray-900">Riwayat</div>
                        </div>
                    </GridItem>
                </Grid>
            </div>

            <!-- Empty State -->
            <div class="mt-8">
                <Empty description="Belum ada data stok" />
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
</style>
