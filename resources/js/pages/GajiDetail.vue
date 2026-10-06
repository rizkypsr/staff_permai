<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { NavBar } from 'vant';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/gaji';

interface Baris {
    nama: string;
    keterangan: string;
    nominal: number;
}

const props = defineProps<{
    slip: {
        periode: string;
        label: string;
        hari_masuk: number;
        hari_telat: number;
        hari_absen: number;
        total_pendapatan: number;
        total_potongan: number;
        utang_awal: number;
        utang_baru: number;
        utang_pengajuan: number;
        utang_potong: number;
        utang_sisa: number;
        total_diterima: number;
        catatan: string;
    };
    pendapatan: Baris[];
    potongan: Baris[];
}>();

const angka = (nilai: number): string => nilai.toLocaleString('id-ID');

/** Minus hanya kalau memang ada potongan, supaya tidak pernah tampil "-0". */
const minus = (nilai: number): string =>
    nilai > 0 ? `-${angka(nilai)}` : angka(nilai);

const totalPotongan = computed(
    () => props.slip.total_potongan + props.slip.utang_potong,
);

const adaUtang = computed(
    () =>
        props.slip.utang_awal !== 0 ||
        props.slip.utang_baru !== 0 ||
        props.slip.utang_pengajuan !== 0 ||
        props.slip.utang_potong !== 0 ||
        props.slip.utang_sisa !== 0,
);
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    :title="`Slip ${slip.label}`"
                    left-arrow
                    @click-left="router.visit(index().url)"
                />
            </div>

            <div class="flex flex-col gap-4 p-4 text-sm">
                <section class="rounded-lg bg-white p-4 shadow-sm">
                    <h3 class="mb-2 font-semibold text-gray-700">Absensi</h3>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ slip.hari_masuk + slip.hari_telat }}
                            </div>
                            <div class="text-xs text-gray-500">Hadir</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ slip.hari_telat }}
                            </div>
                            <div class="text-xs text-gray-500">Telat</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-900">
                                {{ slip.hari_absen }}
                            </div>
                            <div class="text-xs text-gray-500">Tidak masuk</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-4 shadow-sm">
                    <h3 class="mb-2 font-semibold text-gray-700">Pendapatan</h3>
                    <div
                        v-for="(baris, i) in pendapatan"
                        :key="`p${i}`"
                        class="flex items-start justify-between gap-3 border-b border-gray-100 py-2"
                    >
                        <div class="min-w-0">
                            <div class="text-gray-900">{{ baris.nama }}</div>
                            <div
                                v-if="baris.keterangan"
                                class="text-xs text-gray-500"
                            >
                                {{ baris.keterangan }}
                            </div>
                        </div>
                        <div class="shrink-0 text-gray-900">
                            {{ angka(baris.nominal) }}
                        </div>
                    </div>
                    <div
                        class="flex justify-between pt-2 font-semibold text-gray-900"
                    >
                        <span>Total pendapatan</span>
                        <span>{{ angka(slip.total_pendapatan) }}</span>
                    </div>
                </section>

                <section class="rounded-lg bg-white p-4 shadow-sm">
                    <h3 class="mb-2 font-semibold text-gray-700">Potongan</h3>
                    <div
                        v-for="(baris, i) in potongan"
                        :key="`k${i}`"
                        class="flex items-start justify-between gap-3 border-b border-gray-100 py-2"
                    >
                        <div class="min-w-0">
                            <div class="text-gray-900">{{ baris.nama }}</div>
                            <div
                                v-if="baris.keterangan"
                                class="text-xs text-gray-500"
                            >
                                {{ baris.keterangan }}
                            </div>
                        </div>
                        <div class="shrink-0 text-red-600">
                            {{ minus(baris.nominal) }}
                        </div>
                    </div>
                    <div
                        v-if="slip.utang_potong > 0"
                        class="flex justify-between gap-3 border-b border-gray-100 py-2"
                    >
                        <span class="text-gray-900">Potong utang</span>
                        <span class="text-red-600">{{
                            minus(slip.utang_potong)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between pt-2 font-semibold text-gray-900"
                    >
                        <span>Total potongan</span>
                        <span>{{ minus(totalPotongan) }}</span>
                    </div>
                </section>

                <section
                    class="flex items-center justify-between rounded-lg bg-[#fec109] p-4 font-bold text-gray-900 shadow-sm"
                >
                    <span>DITERIMA</span>
                    <span class="text-lg"
                        >Rp {{ angka(slip.total_diterima) }}</span
                    >
                </section>

                <section
                    v-if="adaUtang"
                    class="rounded-lg bg-white p-4 shadow-sm"
                >
                    <h3 class="mb-2 font-semibold text-gray-700">Utang</h3>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-600">Sisa bulan lalu</span>
                        <span>{{ angka(slip.utang_awal) }}</span>
                    </div>
                    <div
                        v-if="slip.utang_baru !== 0"
                        class="flex justify-between py-1"
                    >
                        <span class="text-gray-600">Utang baru</span>
                        <span>+{{ angka(slip.utang_baru) }}</span>
                    </div>
                    <div
                        v-if="slip.utang_pengajuan !== 0"
                        class="flex justify-between py-1"
                    >
                        <span class="text-gray-600">Kasbon</span>
                        <span>+{{ angka(slip.utang_pengajuan) }}</span>
                    </div>
                    <div
                        v-if="slip.utang_potong !== 0"
                        class="flex justify-between py-1"
                    >
                        <span class="text-gray-600">Dipotong</span>
                        <span>{{ minus(slip.utang_potong) }}</span>
                    </div>
                    <div
                        class="mt-1 flex justify-between border-t border-gray-100 pt-2 font-semibold text-gray-900"
                    >
                        <span>Sisa utang</span>
                        <span>{{ angka(slip.utang_sisa) }}</span>
                    </div>
                </section>

                <section
                    v-if="slip.catatan"
                    class="rounded-lg bg-white p-4 shadow-sm"
                >
                    <h3 class="mb-1 font-semibold text-gray-700">Catatan</h3>
                    <p class="break-words whitespace-pre-line text-gray-700">
                        {{ slip.catatan }}
                    </p>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
