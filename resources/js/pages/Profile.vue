<script setup>
import { NavBar, Icon, Cell, CellGroup, Empty, Tag } from 'vant'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    user: Object,
    assets: Array,
})

const goBack = () => {
    router.visit('/')
}

const getMaintenanceStatusColor = (asset) => {
    if (asset.tgl_maintenance === '-') {
        return 'warning'
    }
    
    // Check if maintenance is overdue based on periode_maintenance
    const lastMaintenance = new Date(asset.tgl_maintenance)
    const now = new Date()
    const diffMonths = (now.getFullYear() - lastMaintenance.getFullYear()) * 12 + 
                      (now.getMonth() - lastMaintenance.getMonth())
    
    let overdueMonths = 0
    if (asset.periode_maintenance === 'bulanan') {
        overdueMonths = 1
    } else if (asset.periode_maintenance === 'triwulan') {
        overdueMonths = 3
    } else if (asset.periode_maintenance === 'semester') {
        overdueMonths = 6
    } else if (asset.periode_maintenance === 'tahunan') {
        overdueMonths = 12
    }
    
    if (diffMonths >= overdueMonths) {
        return 'danger'
    }
    
    return 'success'
}

const getMaintenanceStatusText = (asset) => {
    if (asset.tgl_maintenance === '-') {
        return 'Belum Pernah'
    }
    
    const color = getMaintenanceStatusColor(asset)
    if (color === 'danger') {
        return 'Perlu Maintenance'
    } else if (color === 'warning') {
        return 'Segera'
    }
    
    return 'Normal'
}
</script>

<template>
    <AppLayout>
        <div class="h-dvh flex flex-col bg-gray-50">
            <!-- Header -->
            <div class="bg-white flex-shrink-0">
                <NavBar title="Profile" left-arrow @click-left="goBack" />
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto">
                <!-- User Info -->
                <div class="bg-white mb-4">
                    <CellGroup inset>
                        <Cell title="Nama" :value="user.nama" />
                        <Cell title="Email" :value="user.email" />
                        <Cell title="Username" :value="user.username" />
                    </CellGroup>
                </div>

                <!-- Assets Section -->
                <div class="bg-white">
                    <div class="px-4 py-3 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Asset</h3>
                        <p class="text-sm text-gray-500 mt-1">Daftar asset yang ditugaskan kepada Anda</p>
                    </div>

                    <div v-if="assets.length > 0" class="pb-20">
                        <div v-for="asset in assets" :key="asset.id" class="border-b border-gray-100 last:border-b-0">
                            <div class="p-4">
                                <!-- Asset Name & Model -->
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 text-base">{{ asset.nama }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ asset.model }}</p>
                                    </div>
                                    <Tag :type="getMaintenanceStatusColor(asset)" size="medium">
                                        {{ getMaintenanceStatusText(asset) }}
                                    </Tag>
                                </div>

                                <!-- Asset Details -->
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <span class="text-gray-500">Tgl. Pembelian:</span>
                                        <p class="font-medium text-gray-900">{{ asset.tgl_pembelian || '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Usia:</span>
                                        <p class="font-medium text-gray-900">{{ asset.usia }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Periode Maintenance:</span>
                                        <p class="font-medium text-gray-900 capitalize">{{ asset.periode_maintenance }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Total Maintenance:</span>
                                        <p class="font-medium text-gray-900">{{ asset.total_record }} kali</p>
                                    </div>
                                </div>

                                <!-- Last Maintenance -->
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <span class="text-gray-500 text-sm">Maintenance Terakhir:</span>
                                            <p class="font-medium text-gray-900">{{ asset.tgl_maintenance }}</p>
                                            <p v-if="asset.keterangan" class="text-sm text-gray-600 mt-1">
                                                {{ asset.keterangan }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
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