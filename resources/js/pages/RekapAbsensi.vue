<script setup>
import { router } from '@inertiajs/vue3'
import { NavBar, Tabs, Tab, Grid, GridItem, List, Cell, Tag, Empty, Picker, Popup } from 'vant'
import { ref, computed, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    years: Array,
    selectedYear: Number,
    months: Array,
    selectedMonth: String,
    absensiData: Array,
    statistics: Object,
})

const activeTab = ref(props.selectedMonth)
const showYearPicker = ref(false)

// Watch for selectedMonth changes to update activeTab
watch(() => props.selectedMonth, (newMonth) => {
    activeTab.value = newMonth
}, { immediate: true })

// Computed untuk mendapatkan tab yang aktif berdasarkan bulan saja
const activeMonthTab = computed(() => {
    if (!props.selectedMonth) return ''
    // Extract month part and find matching tab from months array
    const monthPart = props.selectedMonth.split('-')[1]
    const matchingMonth = props.months.find(m => m.value.endsWith(`-${monthPart}`))
    return matchingMonth ? matchingMonth.value : props.months[0]?.value || ''
})

const yearColumns = computed(() => {
    return props.years.map(year => ({
        text: year.label,
        value: year.value,
    }))
})

const onTabChange = (name) => {
    // Extract month part from the tab name (e.g., "2026-04" -> "04")
    const monthPart = name.split('-')[1]
    // Combine with current selected year
    const newMonth = `${props.selectedYear}-${monthPart}`
    
    activeTab.value = newMonth
    router.get('/rekap-absensi', { 
        year: props.selectedYear,
        month: newMonth 
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const onYearConfirm = ({ selectedOptions }) => {
    const selectedYear = selectedOptions[0].value
    showYearPicker.value = false
    
    router.get('/rekap-absensi', { 
        year: selectedYear,
        month: props.selectedMonth
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const onYearCancel = () => {
    showYearPicker.value = false
}

const handleYearClick = () => {
    showYearPicker.value = true
}

const getStatusColor = (status) => {
    switch (status) {
        case 'MASUK':
            return 'success'
        case 'TELAT':
            return 'warning'
        case 'ALPHA':
            return 'danger'
        case 'LIBUR':
            return 'primary'
        default:
            return 'default'
    }
}

console.log(props.absensiData);

</script>

<template>
    <AppLayout>
        <!-- Header/Navbar -->
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Rekap Absensi" left-arrow @click-left="$inertia.visit('/')" />
        </div>

        <!-- Year Selector -->
        <div class="bg-white px-4 py-3 border-b">
            <div class="flex items-center justify-between cursor-pointer" @click="handleYearClick">
                <span class="font-semibold">Tahun: {{ selectedYear }}</span>
                <span class="text-gray-400">▼</span>
            </div>
        </div>

        <!-- Month Tabs -->
        <div class="bg-white">
            <Tabs :active="activeMonthTab" @change="onTabChange" swipeable>
                <Tab v-for="month in months" :key="month.value" :name="month.value" :title="month.label" />
            </Tabs>
        </div>

        <!-- Statistics Cards -->
        <div class="p-4">
            <Grid :column-num="3" :gutter="8">
                <GridItem>
                    <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                        <div class="text-lg font-bold text-blue-600">{{ statistics.total_hari_kerja }}</div>
                        <div class="text-xs text-gray-600 mt-1">Total Hari Kerja</div>
                    </div>
                </GridItem>
                <GridItem>
                    <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                        <div class="text-lg font-bold text-green-600">{{ statistics.total_hari_masuk }}</div>
                        <div class="text-xs text-gray-600 mt-1">Total Hari Masuk</div>
                    </div>
                </GridItem>
                <GridItem>
                    <div class="bg-white rounded-lg p-3 text-center shadow-sm">
                        <div class="text-lg font-bold text-orange-600">{{ statistics.total_hari_telat }}</div>
                        <div class="text-xs text-gray-600 mt-1">Total Hari Telat</div>
                    </div>
                </GridItem>
            </Grid>
        </div>

        <!-- Absensi List -->
        <div class="px-4 pb-4">
            <div class="bg-white rounded-lg overflow-hidden shadow-sm">
                <List v-if="absensiData.length > 0">
                    <Cell
                        v-for="item in absensiData"
                        :key="item.id"
                        :title="item.tgl_formatted"
                        :label="`Jam Masuk: ${item.jam_masuk}`"
                    >
                        <template #value>
                            <Tag :type="getStatusColor(item.status)" size="medium">
                                {{ item.status }}
                            </Tag>
                        </template>
                    </Cell>
                </List>
                <Empty v-else description="Tidak ada data absensi" />
            </div>
        </div>

        <!-- Year Picker Popup -->
        <Popup :show="showYearPicker" position="center" round :style="{ width: '90%' }" @click-overlay="onYearCancel">
            <Picker 
                :columns="yearColumns" 
                :model-value="[selectedYear]"
                title="Pilih Tahun"
                @confirm="onYearConfirm" 
                @cancel="onYearCancel" 
            />
        </Popup>
    </AppLayout>
</template>

<style scoped>
:deep(.van-nav-bar) {
    background-color: #ffffff;
}

:deep(.van-nav-bar__title) {
    font-weight: 600;
}

:deep(.van-tabs__nav) {
    background-color: #ffffff;
}

:deep(.van-tab) {
    font-size: 14px;
}

:deep(.van-cell__title) {
    font-size: 15px;
    font-weight: 500;
}

:deep(.van-cell__label) {
    font-size: 13px;
    margin-top: 4px;
}

:deep(.van-tag) {
    font-weight: 600;
    font-size: 12px;
}
</style>