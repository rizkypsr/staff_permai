<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Empty, Icon, NavBar } from 'vant';
import AppLayout from '@/layouts/AppLayout.vue';
import { home } from '@/routes';
import { show } from '@/routes/gaji';

interface Slip {
    periode: string;
    label: string;
    total_diterima: number;
    utang_sisa: number;
}

defineProps<{
    slip: Slip[];
}>();

const rupiah = (nilai: number): string => `Rp ${nilai.toLocaleString('id-ID')}`;
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    title="Slip Gaji"
                    left-arrow
                    @click-left="router.visit(home().url)"
                />
            </div>

            <div class="flex flex-col gap-3 p-4">
                <template v-if="slip.length > 0">
                    <Link
                        v-for="item in slip"
                        :key="item.periode"
                        :href="show(item.periode).url"
                        class="block rounded-lg bg-white p-4 shadow-sm active:bg-gray-50"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-semibold text-gray-900">
                                {{ item.label }}
                            </div>
                            <Icon name="arrow" class="text-gray-400" />
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <div class="text-xs text-gray-500">
                                    Diterima
                                </div>
                                <div class="font-semibold text-gray-900">
                                    {{ rupiah(item.total_diterima) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-xs text-gray-500">
                                    Sisa utang
                                </div>
                                <div
                                    class="font-semibold"
                                    :class="
                                        item.utang_sisa > 0
                                            ? 'text-amber-600'
                                            : 'text-gray-900'
                                    "
                                >
                                    {{ rupiah(item.utang_sisa) }}
                                </div>
                            </div>
                        </div>
                    </Link>
                </template>

                <Empty v-else description="Belum ada slip gaji" />
            </div>
        </div>
    </AppLayout>
</template>
