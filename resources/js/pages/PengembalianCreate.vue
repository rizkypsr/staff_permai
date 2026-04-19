<script setup>
import { useForm } from '@inertiajs/vue3'
import { NavBar, Form, Field, CellGroup, Button, Picker, Popup, DatePicker, Stepper, showToast } from 'vant'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    pengirimanList: Array,
    latestPengiriman: Object,
})

const showPengirimanPicker = ref(false)
const showDatePicker = ref(false)
const selectedPengiriman = ref(null)
const pengirimanDetailList = ref([])

const form = useForm({
    tgl: new Date(),
    id_pengiriman: props.latestPengiriman?.id || null,
    keterangan: props.latestPengiriman?.keterangan || '',
    produk: [],
})

const pengirimanColumns = computed(() => {
    return props.pengirimanList.map(p => ({
        text: p.kode,
        value: p.id,
    }))
})

const loadPengirimanDetail = async (idPengiriman) => {
    try {
        const response = await fetch(`/pengembalian/pengiriman/${idPengiriman}/detail`)
        const data = await response.json()
        pengirimanDetailList.value = data
        
        // Initialize produk array with qty_kembali = 0
        form.produk = data.map(item => ({
            id_produk: item.id_produk,
            id_satuan: item.id_satuan,
            satuan: item.satuan,
            qty_bawa: item.qty,
            qty_kembali: 0,
        }))
    } catch (error) {
        console.error('Failed to load pengiriman detail:', error)
        pengirimanDetailList.value = []
        form.produk = []
    }
}

// Calculate qty terpakai for each item
const getQtyTerpakai = (index) => {
    const produk = form.produk[index]
    if (!produk) return 0
    return produk.qty_bawa - produk.qty_kembali
}

// Auto-select from latest pengiriman if available
if (props.latestPengiriman) {
    selectedPengiriman.value = {
        text: props.latestPengiriman.kode,
        value: props.latestPengiriman.id,
    }
    // Load detail for latest pengiriman
    loadPengirimanDetail(props.latestPengiriman.id)
}

const onConfirmPengiriman = ({ selectedOptions }) => {
    const selected = props.pengirimanList.find(p => p.id === selectedOptions[0].value)
    if (selected) {
        selectedPengiriman.value = selectedOptions[0]
        form.id_pengiriman = selected.id
        
        // Update keterangan from selected pengiriman
        form.keterangan = selected.keterangan || ''
        
        // Load pengiriman detail
        loadPengirimanDetail(selected.id)
    }
    showPengirimanPicker.value = false
}

const handlePengirimanClick = () => {
    showPengirimanPicker.value = true
}

const handleDateClick = () => {
    showDatePicker.value = true
}

const onConfirmDate = ({ selectedValues }) => {
    form.tgl = new Date(selectedValues[0], selectedValues[1] - 1, selectedValues[2])
    showDatePicker.value = false
}

const onCancelDate = () => {
    showDatePicker.value = false
}

const onCancelPengiriman = () => {
    showPengirimanPicker.value = false
}

const formatDate = (date) => {
    const d = new Date(date)
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
    return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`
}

const handleSubmit = () => {
    // Validate: at least one product must have qty_kembali > 0
    const hasQtyKembali = form.produk.some(p => p.qty_kembali > 0)
    
    if (!hasQtyKembali) {
        showToast({
            message: 'Minimal harus ada 1 produk yang dikembalikan',
            position: 'top',
            wordBreak: 'break-word',
        })
        return
    }
    
    // Format date to Y-m-d
    const tglFormatted = form.tgl.toISOString().split('T')[0]

    // Submit data
    form.transform((data) => ({
        ...data,
        tgl: tglFormatted,
    })).post('/pengembalian', {
        onSuccess: () => {
            showToast({
                message: 'Pengembalian berhasil dibuat',
                type: 'success',
                position: 'top',
                wordBreak: 'break-word',
            })
        },
        onError: (errors) => {
            console.error('Submit error:', errors)
            showToast({
                message: 'Gagal membuat pengembalian',
                position: 'top',
                wordBreak: 'break-word',
            })
        },
    })
}

const pengirimanText = computed(() => {
    return selectedPengiriman.value ? selectedPengiriman.value.text : ''
})

const isFormValid = computed(() => {
    return form.tgl && form.id_pengiriman
})
</script>

<template>
    <AppLayout>
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Tambah Pengembalian" left-arrow @click-left="$inertia.visit('/pengiriman')" />
        </div>

        <div class="pb-24">
            <Form>
                <CellGroup inset>
                    <Field :model-value="formatDate(form.tgl)" is-link readonly label="Tgl. Pengembalian"
                        placeholder="Pilih tanggal" required @click="handleDateClick" />
                    <Field :model-value="pengirimanText" is-link readonly label="No. Pengiriman"
                        placeholder="Pilih no. pengiriman" required @click="handlePengirimanClick" />
                    <Field v-model="form.keterangan" label="Keterangan" type="textarea"
                        placeholder="Masukkan keterangan" rows="3" autosize />
                </CellGroup>
            </Form>

            <!-- Pengiriman Detail List -->
            <div v-if="pengirimanDetailList.length > 0" class="mt-4">
                <div class="px-4 py-2 bg-gray-100 font-semibold text-sm">
                    Detail Produk Pengiriman
                </div>
                <div class="bg-white">
                    <div v-for="(item, index) in pengirimanDetailList" :key="item.id" class="border-b border-gray-200 p-4">
                        <!-- Product Name -->
                        <div class="font-semibold text-sm mb-2">
                            {{ item.nama_produk }}
                        </div>
                        
                        <!-- Satuan -->
                        <div class="text-xs text-gray-600 mb-3">
                            Satuan: {{ item.satuan }}
                        </div>
                        
                        <!-- Qty Info Vertical Layout -->
                        <div class="space-y-3">
                            <!-- Jumlah Dibawa -->
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">Jml. Dibawa</div>
                                <div class="font-semibold">{{ item.qty }}</div>
                            </div>
                            
                            <!-- Jumlah Terpakai (Calculated) -->
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">Jml. Terpakai</div>
                                <div class="font-semibold">{{ getQtyTerpakai(index) }}</div>
                            </div>
                            
                            <!-- Jumlah Dikembalikan (Input) -->
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-600">Jml. Dikembalikan</div>
                                <Stepper 
                                    v-model="form.produk[index].qty_kembali" 
                                    :min="0" 
                                    :max="item.qty"
                                    input-width="50px"
                                    button-size="24px"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Button - Fixed at bottom like tabbar -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white border-t border-gray-200 p-4">
            <Button type="primary" block round size="large" @click="handleSubmit" :disabled="!isFormValid"
                :loading="form.processing" loading-type="spinner">
                Simpan
            </Button>
        </div>

        <Popup :show="showDatePicker" position="center" round :style="{ width: '90%' }" @click-overlay="onCancelDate">
            <DatePicker :model-value="[form.tgl.getFullYear(), form.tgl.getMonth() + 1, form.tgl.getDate()]"
                title="Pilih Tanggal" @confirm="onConfirmDate" @cancel="onCancelDate" />
        </Popup>

        <Popup :show="showPengirimanPicker" position="center" round :style="{ width: '90%' }"
            @click-overlay="onCancelPengiriman">
            <Picker :columns="pengirimanColumns" @confirm="onConfirmPengiriman" @cancel="onCancelPengiriman" />
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

:deep(.van-cell-group--inset) {
    margin: 0;
}

:deep(.van-button--primary) {
    background-color: #fec109;
    border-color: #fec109;
}

/* Increase font sizes throughout the form */
:deep(.van-field__label) {
    font-size: 15px;
    font-weight: 500;
}

:deep(.van-field__value) {
    font-size: 15px;
}

:deep(.van-field__control) {
    font-size: 15px;
}

:deep(.van-button__text) {
    font-size: 15px;
    font-weight: 500;
}

:deep(.van-picker__column-item) {
    font-size: 15px;
}

:deep(.van-picker__title) {
    font-size: 16px;
    font-weight: 600;
}

/* Detail list styling */
:deep(.van-cell__title) {
    font-size: 14px;
    font-weight: 500;
}

:deep(.van-cell__label) {
    font-size: 13px;
}

:deep(.van-cell__value) {
    font-size: 14px;
}
</style>