<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button, Empty, NavBar, Tag } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';
import { index } from '@/routes/uang-makan';

interface Baris {
    id_pengguna: number;
    nama: string;
    jumlah_hari: number;
    total: number;
    menunggu: number;
    nominal: number[];
    rincian_nominal: { nominal: number; hari: number }[];
    hari_hadir: string[];
}

interface Ambil {
    tgl: string;
    tgl_label: string;
    rentang_label: string;
    lewat: boolean;
    hari_ini: boolean;
    baris: Baris[];
    total: number;
}

defineProps<{
    periode: string;
    label: string;
    sebelum: string;
    sesudah: string;
    ambil: Ambil[];
    per_karyawan: {
        id_pengguna: number;
        nama: string;
        jumlah_hari: number;
        total: number;
    }[];
    total: number;
    berjalan: {
        tgl: string;
        tgl_label: string;
        jumlah_staff: number;
        jumlah_hari: number;
        total: number;
        menunggu: number;
    } | null;
}>();

const angka = (nilai: number): string => nilai.toLocaleString('id-ID');
const rupiah = (nilai: number): string => `Rp ${angka(nilai)}`;

/** "3 x 12.000, 1 x 15.000" kalau nominal berubah, selain itu nominal per hari. */
const teksNominal = (baris: Baris): string =>
    baris.rincian_nominal.length > 1
        ? baris.rincian_nominal
              .map((r) => `${r.hari} x ${angka(r.nominal)}`)
              .join(', ')
        : baris.nominal.map(angka).join(' / ');

const pindahBulan = (periode: string) => {
    router.visit(index({ query: { periode } }).url, { preserveScroll: true });
};
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    title="Uang Makan"
                    left-arrow
                    @click-left="router.visit(home().url)"
                />
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-4 py-2"
                >
                    <Button
                        size="small"
                        icon="arrow-left"
                        aria-label="Bulan sebelumnya"
                        @click="pindahBulan(sebelum)"
                    />
                    <span class="font-semibold text-gray-900">{{ label }}</span>
                    <Button
                        size="small"
                        icon="arrow"
                        aria-label="Bulan berikutnya"
                        @click="pindahBulan(sesudah)"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-4 p-4 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="text-xs text-gray-500">
                            Total {{ label }}
                        </div>
                        <div class="mt-1 text-lg font-semibold text-amber-600">
                            {{ rupiah(total) }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ ambil.length }} hari ambil
                        </div>
                    </div>
                    <div
                        v-if="berjalan"
                        class="rounded-lg border border-blue-200 bg-blue-50 p-3"
                    >
                        <div class="text-xs text-gray-600">
                            Terkumpul sampai hari ini
                        </div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            {{ rupiah(berjalan.total) }}
                        </div>
                        <div class="text-xs text-gray-600">
                            {{ berjalan.jumlah_staff }} staff, diambil
                            {{ berjalan.tgl_label }}
                        </div>
                    </div>
                </div>

                <Empty
                    v-if="ambil.length === 0"
                    description="Tidak ada hari ambil di bulan ini"
                />

                <section
                    v-for="item in ambil"
                    :key="item.tgl"
                    class="overflow-hidden rounded-lg bg-white shadow-sm"
                >
                    <div
                        class="flex items-start justify-between gap-2 border-b border-gray-100 p-4"
                        :class="{ 'bg-gray-50': !item.lewat }"
                    >
                        <div class="min-w-0">
                            <div class="font-semibold text-gray-900">
                                {{ item.tgl_label }}
                            </div>
                            <div class="text-xs text-gray-500">
                                untuk {{ item.rentang_label }}
                            </div>
                        </div>
                        <Tag v-if="item.hari_ini" type="primary" size="medium">
                            Hari ini
                        </Tag>
                        <Tag
                            v-else-if="!item.lewat"
                            type="default"
                            size="medium"
                        >
                            Belum tiba
                        </Tag>
                    </div>

                    <div
                        v-if="item.baris.length === 0"
                        class="p-4 text-center text-gray-500"
                    >
                        Tidak ada staff yang dapat uang makan di hari ambil ini.
                    </div>

                    <div
                        v-for="baris in item.baris"
                        :key="baris.id_pengguna"
                        class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-3 last:border-b-0"
                    >
                        <div class="min-w-0">
                            <div class="font-medium text-gray-900">
                                {{ baris.nama }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ baris.jumlah_hari }} hari ·
                                {{ teksNominal(baris) }}
                            </div>
                            <div
                                v-if="baris.hari_hadir.length > 0"
                                class="text-xs text-gray-400"
                            >
                                {{ baris.hari_hadir.join(', ') }}
                            </div>
                            <div
                                v-if="baris.menunggu > 0"
                                class="text-xs text-amber-700"
                            >
                                {{ baris.menunggu }} hari menunggu approve
                            </div>
                        </div>
                        <div
                            class="shrink-0 font-semibold"
                            :class="
                                baris.total > 0
                                    ? 'text-gray-900'
                                    : 'text-gray-400'
                            "
                        >
                            {{ rupiah(baris.total) }}
                        </div>
                    </div>

                    <div
                        class="flex justify-between bg-gray-50 px-4 py-3 font-semibold"
                    >
                        <span class="text-gray-700"
                            >Total {{ item.baris.length }} staff</span
                        >
                        <span class="text-gray-900">{{
                            rupiah(item.total)
                        }}</span>
                    </div>
                </section>

                <section
                    v-if="per_karyawan.length > 0"
                    class="rounded-lg bg-white p-4 shadow-sm"
                >
                    <h3 class="mb-2 font-semibold text-gray-700">
                        Per staff, {{ label }}
                    </h3>
                    <div
                        v-for="k in per_karyawan"
                        :key="k.id_pengguna"
                        class="flex justify-between gap-3 py-1"
                    >
                        <span class="text-gray-700"
                            >{{ k.nama }}
                            <span class="text-xs text-gray-400"
                                >({{ k.jumlah_hari }} hari)</span
                            ></span
                        >
                        <span class="text-gray-900">{{ rupiah(k.total) }}</span>
                    </div>
                    <div
                        class="mt-2 flex justify-between border-t border-gray-100 pt-2 font-semibold"
                    >
                        <span>Total</span>
                        <span class="text-amber-600">{{ rupiah(total) }}</span>
                    </div>
                </section>

                <p class="px-1 text-xs text-gray-500">
                    Dihitung otomatis dari absensi: setiap hari ambil membayar
                    hari hadir (masuk atau telat) sejak hari ambil sebelumnya.
                    Hari yang masih menunggu approve belum dihitung.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
