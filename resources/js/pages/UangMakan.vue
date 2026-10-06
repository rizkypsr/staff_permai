<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button, Empty, NavBar, Tag } from 'vant';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';
import { index } from '@/routes/uang-makan';

interface HariChip {
    tgl: string;
    label: string;
    kelas: 'hadir' | 'menunggu' | 'tidak-dapat';
}

interface Ambil {
    tgl: string;
    tgl_label: string;
    rentang_label: string;
    lewat: boolean;
    hari_ini: boolean;
    jumlah_hari: number;
    total: number;
    menunggu: number;
    rincian_nominal: { nominal: number; hari: number }[];
    hari: HariChip[];
}

const props = defineProps<{
    periode: string;
    label: string;
    sebelum: string;
    sesudah: string;
    terdaftar: boolean;
    nominal_bulan: { rentang_label: string; nominal: number | null }[];
    ambil: Ambil[];
    total: number;
    total_lewat: number;
    jumlah_hari: number;
    menunggu: number;
    berjalan: {
        tgl: string;
        tgl_label: string;
        jumlah_hari: number;
        total: number;
        menunggu: number;
    } | null;
}>();

const rupiah = (nilai: number): string => `Rp ${nilai.toLocaleString('id-ID')}`;

const nominalTerakhir = computed(
    () => props.nominal_bulan[props.nominal_bulan.length - 1]?.nominal ?? null,
);

const pindahBulan = (periode: string) => {
    router.visit(index({ query: { periode } }).url, { preserveScroll: true });
};

const kelasChip: Record<HariChip['kelas'], string> = {
    hadir: 'border-green-300 bg-green-50 text-green-700',
    menunggu: 'border-amber-300 bg-amber-50 text-amber-700',
    'tidak-dapat': 'border-gray-200 bg-gray-50 text-gray-400 line-through',
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
                <Empty
                    v-if="!terdaftar"
                    :description="`Anda tidak terdaftar menerima uang makan di ${label}.`"
                />

                <template v-else>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-white p-3 shadow-sm">
                            <div class="text-xs text-gray-500">
                                Per hari hadir
                            </div>
                            <div
                                class="mt-1 text-lg font-semibold text-gray-900"
                            >
                                {{
                                    nominalTerakhir !== null
                                        ? rupiah(nominalTerakhir)
                                        : '-'
                                }}
                            </div>
                            <template v-if="nominal_bulan.length > 1">
                                <div
                                    v-for="segmen in nominal_bulan"
                                    :key="segmen.rentang_label"
                                    class="text-xs text-gray-500"
                                >
                                    {{ segmen.rentang_label }}:
                                    {{
                                        segmen.nominal !== null
                                            ? rupiah(segmen.nominal)
                                            : 'tidak dapat'
                                    }}
                                </div>
                            </template>
                        </div>
                        <div class="rounded-lg bg-white p-3 shadow-sm">
                            <div class="text-xs text-gray-500">
                                Total {{ label }}
                            </div>
                            <div
                                class="mt-1 text-lg font-semibold text-amber-600"
                            >
                                {{ rupiah(total) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ jumlah_hari }} hari hadir
                            </div>
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
                            {{ berjalan.jumlah_hari }} hari, diambil
                            {{ berjalan.tgl_label }}
                        </div>
                        <div
                            v-if="berjalan.menunggu > 0"
                            class="mt-1 text-xs text-amber-700"
                        >
                            {{ berjalan.menunggu }} hari masih menunggu approve,
                            belum dihitung.
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-2 px-1 font-semibold text-gray-700">
                            Hari Ambil
                        </h3>

                        <div
                            v-if="ambil.length > 0"
                            class="flex flex-col gap-3"
                        >
                            <div
                                v-for="item in ambil"
                                :key="item.tgl"
                                class="rounded-lg bg-white p-4 shadow-sm"
                                :class="{ 'opacity-70': !item.lewat }"
                            >
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="font-semibold text-gray-900"
                                        >
                                            {{ item.tgl_label }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            untuk {{ item.rentang_label }}
                                        </div>
                                    </div>
                                    <Tag
                                        v-if="item.hari_ini"
                                        type="primary"
                                        size="medium"
                                    >
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
                                    v-if="item.hari.length > 0"
                                    class="mt-2 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="hari in item.hari"
                                        :key="hari.tgl"
                                        class="rounded-full border px-2 py-0.5 text-xs"
                                        :class="kelasChip[hari.kelas]"
                                    >
                                        {{ hari.label }}
                                    </span>
                                </div>

                                <p
                                    v-if="item.menunggu > 0"
                                    class="mt-2 text-xs text-amber-700"
                                >
                                    {{ item.menunggu }} hari masih menunggu
                                    approve, belum dihitung.
                                </p>

                                <div
                                    class="mt-3 flex items-end justify-between gap-2 border-t border-gray-100 pt-3"
                                >
                                    <div class="text-xs text-gray-500">
                                        <div>
                                            {{ item.jumlah_hari }} hari hadir
                                        </div>
                                        <div
                                            v-for="r in item.rincian_nominal"
                                            :key="r.nominal"
                                        >
                                            {{ r.hari }} x
                                            {{ rupiah(r.nominal) }}
                                        </div>
                                    </div>
                                    <div
                                        class="text-base font-semibold text-gray-900"
                                    >
                                        {{ rupiah(item.total) }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-between rounded-lg bg-white p-4 font-semibold shadow-sm"
                            >
                                <span class="text-gray-700"
                                    >Total ({{ jumlah_hari }} hari)</span
                                >
                                <span class="text-base text-amber-600">{{
                                    rupiah(total)
                                }}</span>
                            </div>
                        </div>

                        <Empty
                            v-else
                            description="Tidak ada hari ambil di bulan ini"
                        />
                    </div>

                    <p class="px-1 text-xs text-gray-500">
                        Dihitung otomatis dari absensi: setiap hari ambil
                        membayar hari hadir (masuk atau telat) sejak hari ambil
                        sebelumnya. Hari yang masih menunggu approve belum
                        dihitung.
                        <span class="text-green-700">Hijau</span> dibayar,
                        <span class="text-amber-700">kuning</span> menunggu
                        approve, <span class="line-through">dicoret</span> hadir
                        tapi tidak dapat uang makan.
                    </p>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
