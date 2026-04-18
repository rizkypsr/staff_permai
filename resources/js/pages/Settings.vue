<script setup>
import { ref, onMounted } from 'vue'
import { NavBar, Cell, CellGroup, RadioGroup, Radio } from 'vant'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
})

// Font size options with very subtle scales
const fontSizeOptions = [
    { label: 'Kecil', value: 'small', scale: 0.97 },
    { label: 'Normal', value: 'normal', scale: 1.0 },
    { label: 'Sedang', value: 'medium', scale: 1.03 },
    { label: 'Besar', value: 'large', scale: 1.06 },
]

// Current font size selection
const selectedFontSize = ref('normal')

// Load saved font size from localStorage
onMounted(() => {
    const savedFontSize = localStorage.getItem('app-font-size')
    if (savedFontSize) {
        selectedFontSize.value = savedFontSize
        const option = fontSizeOptions.find(opt => opt.value === savedFontSize)
        if (option) {
            applyFontSize(option.scale)
        }
    }
})

// Apply font size to document root
const applyFontSize = (scale) => {
    document.documentElement.style.setProperty('--font-scale', scale.toString())
    
    // Add CSS class to enable font scaling
    if (!document.documentElement.classList.contains('font-scaling-enabled')) {
        document.documentElement.classList.add('font-scaling-enabled')
    }
}

// Handle font size change
const handleFontSizeChange = (value) => {
    selectedFontSize.value = value
    const option = fontSizeOptions.find(opt => opt.value === value)
    if (option) {
        applyFontSize(option.scale)
        localStorage.setItem('app-font-size', value)
    }
}

// Get current font size label for display
const getCurrentFontSizeLabel = () => {
    const option = fontSizeOptions.find(opt => opt.value === selectedFontSize.value)
    return option ? option.label : 'Normal'
}
</script>

<template>
    <AppLayout>
        <!-- Header -->
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Pengaturan" left-arrow @click-left="$inertia.visit('/')" />
        </div>

        <!-- Settings Content -->
        <div class="p-4">
            <!-- Font Size Setting -->
            <CellGroup inset>
                <Cell title="Ukuran Font" />
            </CellGroup>
            
            <!-- Font Size Options -->
            <div class="mt-4">
                <RadioGroup v-model="selectedFontSize" @change="handleFontSizeChange">
                    <CellGroup inset>
                        <Cell
                            v-for="option in fontSizeOptions"
                            :key="option.value"
                            :title="option.label"
                            clickable
                            @click="handleFontSizeChange(option.value)"
                        >
                            <template #right-icon>
                                <Radio :name="option.value" checked-color="#fec109" />
                            </template>
                        </Cell>
                    </CellGroup>
                </RadioGroup>
            </div>

            <!-- Preview Text -->
            <div class="mt-6 p-4 bg-white rounded-lg shadow-sm">
                <div class="text-sm text-gray-600 mb-3 font-medium">Preview:</div>
                <div class="space-y-3">
                    <div class="text-lg font-semibold text-gray-900">Judul Halaman</div>
                    <div class="text-base text-gray-800">Ini adalah contoh teks normal untuk melihat perubahan ukuran font.</div>
                    <div class="text-sm text-gray-600">Teks kecil untuk detail informasi.</div>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                <div class="flex items-start">
                    <div class="text-blue-600 mr-2">ℹ️</div>
                    <div class="text-sm text-blue-700">
                        <strong>Info:</strong> Pengaturan ukuran font akan tersimpan dan diterapkan di seluruh aplikasi.
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
}

:deep(.van-cell-group--inset) {
    margin: 0;
}

:deep(.van-radio) {
    margin-right: 0;
}

:deep(.van-radio__icon) {
    margin-right: 0;
}
</style>