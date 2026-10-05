<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Empty, NavBar, Tag } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/penagihan';

type StatusPenagihan = 0 | 1;

interface Nota {
    id: number;
    no_nota: string;
    tgl_nota: string;
    nama_pelanggan: string;
    alamat: string;
    no_telp: string;
    sisa_tagihan: number;
    sisa_sekarang: number;
    lunas: boolean;
}

defineProps<{
    penagihan: {
        id: number;
        no_transaksi: string;
        tgl_formatted: string;
        status: StatusPenagihan;
        keterangan: string;
    };
    nota: Nota[];
    total: {
        sisa_tagihan: number;
        sisa_sekarang: number;
    };
}>();

const statusTag: Record<
    StatusPenagihan,
    { label: string; type: 'warning' | 'success' }
> = {
    0: { label: 'Dalam penagihan', type: 'warning' },
    1: { label: 'Selesai', type: 'success' },
};

const rupiah = (nilai: number): string => `Rp ${nilai.toLocaleString('id-ID')}`;

const telLink = (nomor: string): string =>
    `tel:${nomor.replace(/[^\d+]/g, '')}`;
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    :title="penagihan.no_transaksi"
                    left-arrow
                    @click-left="router.visit(index().url)"
                />
            </div>

            <div class="flex flex-col gap-4 p-4">
                <div class="rounded-lg bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="text-sm text-gray-500">
                                {{ penagihan.tgl_formatted }}
                            </div>
                            <div class="font-semibold text-gray-900">
                                {{ penagihan.no_transaksi }}
                            </div>
                        </div>
                        <Tag
                            :type="statusTag[penagihan.status].type"
                            size="medium"
                        >
                            {{ statusTag[penagihan.status].label }}
                        </Tag>
                    </div>
                    <p
                        v-if="penagihan.keterangan"
                        class="mt-2 text-sm break-words text-gray-700"
                    >
                        {{ penagihan.keterangan }}
                    </p>
                </div>

                <div>
                    <h3 class="mb-2 px-1 font-semibold text-gray-700">
                        Daftar Nota ({{ nota.length }})
                    </h3>

                    <div v-if="nota.length > 0" class="flex flex-col gap-3">
                        <div
                            v-for="item in nota"
                            :key="item.id"
                            class="rounded-lg bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900">
                                        {{ item.no_nota }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ item.tgl_nota }}
                                    </div>
                                </div>
                                <Tag
                                    v-if="item.lunas"
                                    type="success"
                                    size="medium"
                                >
                                    Lunas
                                </Tag>
                            </div>

                            <div class="mt-3 space-y-1 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ item.nama_pelanggan || '-' }}
                                </div>
                                <div class="break-words text-gray-600">
                                    {{ item.alamat || '-' }}
                                </div>
                                <a
                                    v-if="item.no_telp"
                                    :href="telLink(item.no_telp)"
                                    class="inline-block font-medium text-blue-600"
                                >
                                    {{ item.no_telp }}
                                </a>
                                <div v-else class="text-gray-400">
                                    Tidak ada telepon
                                </div>
                            </div>

                            <div
                                class="mt-3 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3 text-sm"
                            >
                                <div>
                                    <div class="text-xs text-gray-500">
                                        Sisa saat ditugaskan
                                    </div>
                                    <div class="font-medium text-gray-900">
                                        {{ rupiah(item.sisa_tagihan) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">
                                        Sisa sekarang
                                    </div>
                                    <div
                                        class="font-semibold"
                                        :class="
                                            item.lunas
                                                ? 'text-green-600'
                                                : 'text-amber-600'
                                        "
                                    >
                                        {{ rupiah(item.sisa_sekarang) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-2 rounded-lg bg-white p-4 text-sm shadow-sm"
                        >
                            <div>
                                <div class="text-xs text-gray-500">
                                    Total saat ditugaskan
                                </div>
                                <div class="font-semibold text-gray-900">
                                    {{ rupiah(total.sisa_tagihan) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">
                                    Total sisa sekarang
                                </div>
                                <div class="font-semibold text-amber-600">
                                    {{ rupiah(total.sisa_sekarang) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <Empty v-else description="Tidak ada nota" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
