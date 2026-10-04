<script setup>
import { router } from '@inertiajs/vue3';
import { NavBar, Cell, CellGroup, Empty } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
    user: Object,
    assets: Array,
});

const goBack = () => {
    router.visit('/');
};
</script>

<template>
    <AppLayout>
        <div class="flex h-dvh flex-col bg-gray-50">
            <!-- Header -->
            <div class="flex-shrink-0 bg-white">
                <NavBar title="Profile" left-arrow @click-left="goBack" />
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- User Info -->
                <div class="mb-4 bg-white">
                    <CellGroup inset>
                        <Cell title="Nama" :value="user.nama" />
                        <Cell title="Email" :value="user.email" />
                        <Cell title="Username" :value="user.username" />
                    </CellGroup>
                </div>

                <!-- Assets Section -->
                <div class="bg-white">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <h3 class="text-lg font-semibold text-gray-700">
                            Asset
                        </h3>
                    </div>

                    <div v-if="assets.length > 0" class="pb-20">
                        <div
                            v-for="asset in assets"
                            :key="asset.id"
                            class="border-b border-gray-100 last:border-b-0"
                        >
                            <div class="p-4">
                                <h4
                                    class="text-base font-semibold text-gray-900"
                                >
                                    {{ asset.nama }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-8">
                        <Empty description="Tidak ada asset yang ditugaskan" />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.van-nav-bar__title) {
    font-weight: 600;
}
</style>
