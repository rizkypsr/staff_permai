<script setup>
import { useForm } from '@inertiajs/vue3'
import { NavBar, Steps, Step, Form, Field, CellGroup, Button, Picker, Popup, DatePicker, Stepper, Search, Checkbox, Cell, showToast, showLoadingToast, closeToast } from 'vant'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const props = defineProps({
    auth: Object,
    fakturList: Array,
    latestFaktur: Object,
    produkNota: Array,
    pipaList: Array,
    penggunaList: Array,
})

const activeStep = ref(0)
const showFakturPicker = ref(false)
const showPipaPicker = ref(false)
const showDatePicker = ref(false)
const selectedPelanggan = ref(null)
const selectedFaktur = ref(null)
const produkNotaList = ref([...props.produkNota])
const selectedPipaList = ref([])
const selectedPersonIds = ref([])
const pipaSearchQuery = ref('')

// Auto-select from latest faktur if available
if (props.latestFaktur) {
    selectedPelanggan.value = {
        text: props.latestFaktur.pelanggan_nama,
        value: props.latestFaktur.id_pelanggan,
    }
    selectedFaktur.value = {
        text: props.latestFaktur.no_transaksi,
        value: props.latestFaktur.id,
    }
}

const form = useForm({
    tgl: new Date(),
    id_pelanggan: props.latestFaktur?.id_pelanggan || null,
    id_faktur: props.latestFaktur?.id || null,
    alamat: props.latestFaktur?.pelanggan_alamat || '',
    keterangan: props.latestFaktur?.keterangan || '',
})

const fakturColumns = computed(() => {
    return props.fakturList.map(f => ({
        text: `${f.no_transaksi} - ${f.pelanggan_nama}`,
        value: f.id,
    }))
})

const filteredPipaList = computed(() => {
    if (!pipaSearchQuery.value) {
        return props.pipaList
    }

    const query = pipaSearchQuery.value.toLowerCase()
    return props.pipaList.filter(pipa =>
        pipa.kode.toLowerCase().includes(query) ||
        pipa.nama.toLowerCase().includes(query) ||
        pipa.stok.toString().includes(query)
    )
})

const onConfirmFaktur = ({ selectedOptions }) => {
    const selected = props.fakturList.find(f => f.id === selectedOptions[0].value)
    if (selected) {
        selectedFaktur.value = selectedOptions[0]
        form.id_faktur = selected.id

        // Update pelanggan based on selected faktur
        selectedPelanggan.value = {
            text: selected.pelanggan_nama,
            value: selected.id_pelanggan,
        }
        form.id_pelanggan = selected.id_pelanggan

        // Update alamat and keterangan
        form.alamat = selected.pelanggan_alamat || ''
        form.keterangan = selected.keterangan || ''

        // Load produk nota for selected faktur
        loadProdukNota(selected.id)
    }
    showFakturPicker.value = false
}

const loadProdukNota = async (idFaktur) => {
    try {
        const response = await fetch(`/pengiriman/faktur/${idFaktur}/produk-nota`)
        const data = await response.json()
        produkNotaList.value = data // qty_kirim sudah ada dari backend
    } catch (error) {
        console.error('Failed to load produk nota:', error)
    }
}

const handleFakturClick = () => {
    showFakturPicker.value = true
}

const handlePipaClick = () => {
    pipaSearchQuery.value = '' // Reset search when opening
    showPipaPicker.value = true
}

const selectPipaFromList = (pipa) => {
    if (!selectedPipaList.value.find(p => p.id === pipa.id)) {
        selectedPipaList.value.push({
            id: pipa.id,
            kode: pipa.kode,
            nama: pipa.nama,
            qty: pipa.stok, // Set qty to stok
        })
    }
    showPipaPicker.value = false
    pipaSearchQuery.value = ''
}

const onCancelPipa = () => {
    showPipaPicker.value = false
    pipaSearchQuery.value = ''
}

const removePipa = (index) => {
    selectedPipaList.value.splice(index, 1)
}

const togglePerson = (personId) => {
    const index = selectedPersonIds.value.indexOf(personId)
    if (index > -1) {
        selectedPersonIds.value.splice(index, 1)
    } else {
        selectedPersonIds.value.push(personId)
    }
}

const isPersonSelected = (personId) => {
    return selectedPersonIds.value.includes(personId)
}

const selectedPersonList = computed(() => {
    return props.penggunaList.filter(p => selectedPersonIds.value.includes(p.id))
})

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

const onCancelFaktur = () => {
    showFakturPicker.value = false
}

const formatDate = (date) => {
    const d = new Date(date)
    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
    return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`
}

const handleNext = () => {
    if (activeStep.value < 3) {
        activeStep.value++
    } else {
        // Step 4 - Submit
        handleSubmit()
    }
}

const handleSubmit = () => {
    // Validate: minimal 1 produk nota dengan qty_kirim > 0
    const produkNota = produkNotaList.value.filter(item => item.qty_kirim > 0)

    if (produkNota.length === 0) {
        showToast({
            message: 'Minimal 1 produk nota harus memiliki qty kirim > 0',
            type: 'fail',
            wordBreak: 'break-word',
        })
        return
    }

    // Prepare produk nota data
    const produkNotaData = produkNota.map(item => ({
        id_produk: item.id_produk,
        qty_kirim: item.qty_kirim,
        id_satuan: item.id_satuan,
        satuan: item.satuan,
        uraian: item.uraian,
        harga_satuan: item.harga_satuan,
        diskon: item.diskon,
    }))

    // Prepare produk pipa data
    const produkPipa = selectedPipaList.value.map(item => ({
        id_produk: item.id,
        qty: item.qty,
    }))

    // Prepare person ids
    const personIds = selectedPersonIds.value

    // Format date to Y-m-d
    const tglFormatted = form.tgl.toISOString().split('T')[0]

    // Submit data
    form.transform((data) => ({
        ...data,
        tgl: tglFormatted,
        produk_nota: produkNotaData,
        produk_pipa: produkPipa,
        person_ids: personIds,
    })).post('/pengiriman', {
        onSuccess: () => {
            showToast({
                message: 'Pengiriman berhasil dibuat',
                type: 'success',
                wordBreak: 'break-word',
            })
        },
        onError: (errors) => {
            console.error('Submit error:', errors)
            const errorMessage = errors.error || 'Gagal membuat pengiriman'
            showToast({
                message: errorMessage,
                type: 'fail',
                wordBreak: 'break-word',
            })
        },
    })
}

const handlePrev = () => {
    if (activeStep.value > 0) {
        activeStep.value--
    }
}

const buttonText = computed(() => {
    return activeStep.value === 3 ? 'Simpan' : 'Selanjutnya'
})

const isStep0 = computed(() => activeStep.value === 0)
const isStep1 = computed(() => activeStep.value === 1)
const isStep2 = computed(() => activeStep.value === 2)
const isStep3 = computed(() => activeStep.value === 3)
const showBackButton = computed(() => activeStep.value > 0)

const pelangganText = computed(() => {
    return selectedPelanggan.value ? selectedPelanggan.value.text : ''
})

const fakturText = computed(() => {
    return selectedFaktur.value ? selectedFaktur.value.text : ''
})

const isStep1Valid = computed(() => {
    return form.tgl &&
        form.id_pelanggan &&
        form.id_faktur &&
        form.alamat &&
        form.alamat.trim() !== ''
})

const isNextButtonDisabled = computed(() => {
    if (activeStep.value === 0) {
        return !isStep1Valid.value
    }
    if (activeStep.value === 3) {
        return selectedPersonIds.value.length === 0
    }
    return false
})

console.log(props.latestFaktur);

</script>

<template>
    <AppLayout>
        <div class="sticky top-0 z-10 bg-white">
            <NavBar title="Tambah Pengiriman" left-arrow @click-left="$inertia.visit('/pengiriman')" />
        </div>

        <div class="bg-white px-4 py-4">
            <Steps :active="activeStep">
                <Step>Pengiriman</Step>
                <Step>Nota</Step>
                <Step>Pipa</Step>
                <Step>Karyawan</Step>
            </Steps>
        </div>

        <div class="pb-24">
            <div v-show="isStep0">
                <Form>
                    <CellGroup inset>
                        <Field :model-value="formatDate(form.tgl)" is-link readonly label="Tgl. Pengiriman"
                            placeholder="Pilih tanggal" required @click="handleDateClick" />
                        <Field :model-value="pelangganText" is-link readonly label="Pelanggan"
                            placeholder="Pilih pelanggan" required disabled />
                        <Field :model-value="fakturText" is-link readonly label="No. Nota" placeholder="Pilih no. nota"
                            required @click="handleFakturClick" />
                        <Field v-model="form.alamat" label="Alamat Pengiriman" type="textarea"
                            placeholder="Masukkan alamat pengiriman" rows="3" autosize required />
                        <Field v-model="form.keterangan" label="Keterangan" type="textarea"
                            placeholder="Masukkan keterangan (opsional)" rows="3" autosize />
                    </CellGroup>
                </Form>
            </div>

            <div v-show="isStep1">
                <div v-if="produkNotaList.length > 0" class="space-y-3">
                    <div v-for="(item, index) in produkNotaList" :key="item.id"
                        class="bg-white rounded-lg p-4 shadow-sm">
                        <div class="mb-3">
                            <div class="text-sm font-semibold text-gray-900 mb-1">{{ item.kode_nama }}</div>
                            <div class="text-xs text-gray-600">{{ item.uraian }}</div>
                            <div class="text-xs text-gray-600 mt-1">Qty: {{ item.qty }} {{ item.satuan }}</div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700">Qty Kirim:</span>
                            <Stepper v-model="produkNotaList[index].qty_kirim" :min="0" :max="item.qty" integer />
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    Tidak ada produk nota
                </div>
            </div>

            <div v-show="isStep2" class="p-4">
                <!-- Add Button -->
                <div class="mb-4">
                    <Button type="primary" block round @click="handlePipaClick">
                        + Tambah Pipa
                    </Button>
                </div>

                <!-- Selected Pipa List -->
                <div v-if="selectedPipaList.length > 0">
                    <div class="text-xs text-gray-600 px-2 mb-2">Pipa Terpilih:</div>
                    <div class="space-y-3">
                        <div v-for="(pipa, index) in selectedPipaList" :key="pipa.id"
                            class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <div class="text-sm font-semibold text-gray-900">{{ pipa.kode }} - {{ pipa.nama }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">Qty: {{ pipa.qty }}</div>
                                </div>
                                <Button size="small" type="danger" plain @click="removePipa(index)">
                                    Hapus
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    Belum ada pipa dipilih
                </div>
            </div>

            <div v-show="isStep3" class="p-4">
                <!-- Person List with Checkboxes -->
                <CellGroup inset>
                    <Cell v-for="person in props.penggunaList" :key="person.id" clickable
                        @click="togglePerson(person.id)">
                        <template #title>
                            <span class="text-sm">{{ person.nama }}</span>
                        </template>
                        <template #right-icon>
                            <Checkbox :model-value="isPersonSelected(person.id)"
                                @click.stop="togglePerson(person.id)" />
                        </template>
                    </Cell>
                </CellGroup>

                <!-- Selected Person Summary -->
                <div v-if="selectedPersonList.length > 0" class="mt-4">
                    <div class="text-xs text-gray-600 px-2 mb-2">{{ selectedPersonList.length }} Person Terpilih</div>
                </div>
            </div>
        </div>

        <!-- Bottom Actions - inside AppLayout container -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-white border-t border-gray-200 p-4">
            <div class="flex gap-3">
                <Button v-if="showBackButton" block round @click="handlePrev">
                    Kembali
                </Button>
                <Button type="primary" block round @click="handleNext" :disabled="isNextButtonDisabled"
                    :loading="form.processing" loading-type="spinner">
                    {{ buttonText }}
                </Button>
            </div>
        </div>

        <Popup :show="showDatePicker" position="center" round :style="{ width: '90%' }" @click-overlay="onCancelDate">
            <DatePicker :model-value="[form.tgl.getFullYear(), form.tgl.getMonth() + 1, form.tgl.getDate()]"
                title="Pilih Tanggal" @confirm="onConfirmDate" @cancel="onCancelDate" />
        </Popup>

        <Popup :show="showFakturPicker" position="center" round :style="{ width: '90%' }"
            @click-overlay="onCancelFaktur">
            <Picker :columns="fakturColumns" @confirm="onConfirmFaktur" @cancel="onCancelFaktur" />
        </Popup>

        <Popup :show="showPipaPicker" position="center" round :style="{ width: '90%', height: '70%' }"
            @click-overlay="onCancelPipa">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="flex items-center justify-between p-4">
                    <Button size="small" @click="onCancelPipa">Batal</Button>
                    <div class="font-semibold">Pilih Pipa</div>
                    <div class="w-16"></div>
                </div>

                <Search v-model="pipaSearchQuery" placeholder="Cari kode/nama/qty" shape="round" />

                <!-- List -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div v-if="filteredPipaList.length > 0" class="space-y-2">
                        <div v-for="pipa in filteredPipaList" :key="pipa.id"
                            class="bg-gray-50 rounded-lg p-3 cursor-pointer hover:bg-gray-100 active:bg-gray-200"
                            @click="selectPipaFromList(pipa)">
                            <div class="text-sm font-medium text-gray-900">{{ pipa.kode }} - {{ pipa.nama }}</div>
                            <div class="text-xs text-gray-600 mt-1">Stok: {{ pipa.stok }}</div>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-500 py-8">
                        {{ pipaSearchQuery ? 'Tidak ada hasil pencarian' : 'Tidak ada data pipa' }}
                    </div>
                </div>
            </div>
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
</style>
