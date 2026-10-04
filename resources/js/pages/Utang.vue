<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Button, Empty, NavBar, Tag, showConfirmDialog, showToast } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';
import { create, destroy } from '@/routes/utang';

type StatusPengajuan = 0 | 1 | 2;

interface Pengajuan {
    id: number;
    tgl: string;
    tgl_formatted: string;
    nominal: number;
    keterangan: string;
    status: StatusPengajuan;
    catatan_admin: string;
}

defineProps<{
    pengajuan: Pengajuan[];
    ringkasan: {
        sisa_utang: number;
        menunggu: number;
    };
}>();

const statusTag: Record<
    StatusPengajuan,
    { label: string; type: 'warning' | 'success' | 'danger' }
> = {
    0: { label: 'Menunggu', type: 'warning' },
    1: { label: 'Disetujui', type: 'success' },
    2: { label: 'Ditolak', type: 'danger' },
};

const rupiah = (nilai: number): string => `Rp ${nilai.toLocaleString('id-ID')}`;

const batalkan = async (item: Pengajuan) => {
    try {
        await showConfirmDialog({
            title: 'Batalkan Pengajuan',
            message: `Batalkan pengajuan ${rupiah(item.nominal)}?`,
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Tidak',
        });
    } catch {
        return;
    }

    router.delete(destroy.url(item.id), {
        preserveScroll: true,
        onSuccess: () => {
            showToast({
                message: 'Pengajuan dibatalkan',
                type: 'success',
                wordBreak: 'break-word',
            });
        },
        onHttpException: () => {
            showToast({
                message: 'Pengajuan tidak bisa dibatalkan',
                type: 'fail',
                wordBreak: 'break-word',
            });
            router.reload();

            return false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    title="Utang"
                    left-arrow
                    @click-left="router.visit(home().url)"
                />
            </div>

            <div class="flex flex-col gap-4 p-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="text-xs text-gray-500">Sisa utang</div>
                        <div class="mt-1 text-lg font-semibold text-gray-900">
                            {{ rupiah(ringkasan.sisa_utang) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-white p-3 shadow-sm">
                        <div class="text-xs text-gray-500">
                            Menunggu persetujuan
                        </div>
                        <div class="mt-1 text-lg font-semibold text-amber-600">
                            {{ rupiah(ringkasan.menunggu) }}
                        </div>
                    </div>
                </div>

                <Button
                    type="primary"
                    block
                    round
                    icon="plus"
                    color="#fec109"
                    @click="router.visit(create().url)"
                >
                    Ajukan Utang
                </Button>

                <div>
                    <h3 class="mb-2 px-1 font-semibold text-gray-700">
                        Riwayat Pengajuan
                    </h3>

                    <div
                        v-if="pengajuan.length > 0"
                        class="flex flex-col gap-3"
                    >
                        <div
                            v-for="item in pengajuan"
                            :key="item.id"
                            class="rounded-lg bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-sm text-gray-500">
                                        {{ item.tgl_formatted }}
                                    </div>
                                    <div
                                        class="text-base font-semibold text-gray-900"
                                    >
                                        {{ rupiah(item.nominal) }}
                                    </div>
                                </div>
                                <Tag
                                    :type="statusTag[item.status].type"
                                    size="medium"
                                >
                                    {{ statusTag[item.status].label }}
                                </Tag>
                            </div>

                            <p class="mt-2 text-sm break-words text-gray-700">
                                {{ item.keterangan }}
                            </p>

                            <p
                                v-if="item.status === 2 && item.catatan_admin"
                                class="mt-2 rounded bg-red-50 p-2 text-sm text-red-700"
                            >
                                Catatan admin: {{ item.catatan_admin }}
                            </p>

                            <div
                                v-if="item.status === 0"
                                class="mt-3 flex justify-end"
                            >
                                <Button
                                    size="small"
                                    plain
                                    type="danger"
                                    @click="batalkan(item)"
                                >
                                    Batalkan
                                </Button>
                            </div>
                        </div>
                    </div>

                    <Empty v-else description="Belum ada pengajuan utang" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
