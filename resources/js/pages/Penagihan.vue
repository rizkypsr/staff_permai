<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Empty, Icon, NavBar, Tag } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';
import { show } from '@/routes/penagihan';

type StatusPenagihan = 0 | 1;

interface Tugas {
    id: number;
    no_transaksi: string;
    tgl_formatted: string;
    status: StatusPenagihan;
    keterangan: string;
    jumlah_nota: number;
    total_tagihan: number;
}

defineProps<{
    tugas: Tugas[];
    ringkasan: {
        jumlah_dalam_penagihan: number;
        total_dalam_penagihan: number;
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
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    title="Penagihan"
                    left-arrow
                    @click-left="router.visit(home().url)"
                />
            </div>

            <div class="flex flex-col gap-4 p-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="text-xs text-gray-500">
                            Tugas dalam penagihan
                        </div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            {{ ringkasan.jumlah_dalam_penagihan }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="text-xs text-gray-500">Total tagihan</div>
                        <div class="mt-1 text-lg font-semibold text-amber-600">
                            {{ rupiah(ringkasan.total_dalam_penagihan) }}
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 px-1 font-semibold text-gray-700">
                        Tugas Penagihan
                    </h3>

                    <div v-if="tugas.length > 0" class="flex flex-col gap-3">
                        <Link
                            v-for="item in tugas"
                            :key="item.id"
                            :href="show(item.id).url"
                            class="block rounded-lg bg-white p-4 shadow-sm active:bg-gray-50"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-sm text-gray-500">
                                        {{ item.tgl_formatted }}
                                    </div>
                                    <div
                                        class="truncate font-semibold text-gray-900"
                                    >
                                        {{ item.no_transaksi }}
                                    </div>
                                </div>
                                <Tag
                                    :type="statusTag[item.status].type"
                                    size="medium"
                                >
                                    {{ statusTag[item.status].label }}
                                </Tag>
                            </div>

                            <p
                                v-if="item.keterangan"
                                class="mt-1 text-sm break-words text-gray-600"
                            >
                                {{ item.keterangan }}
                            </p>

                            <div
                                class="mt-3 flex items-center justify-between text-sm"
                            >
                                <span class="text-gray-500">
                                    {{ item.jumlah_nota }} nota
                                </span>
                                <span
                                    class="flex items-center gap-1 font-semibold text-gray-900"
                                >
                                    {{ rupiah(item.total_tagihan) }}
                                    <Icon name="arrow" class="text-gray-400" />
                                </span>
                            </div>
                        </Link>
                    </div>

                    <Empty v-else description="Belum ada tugas penagihan" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
