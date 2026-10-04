<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Button, CellGroup, Field, NavBar, showToast } from 'vant';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, store } from '@/routes/utang';

const form = useForm<{ nominal: number | null; keterangan: string }>({
    nominal: null,
    keterangan: '',
});

const nominalTampil = computed<string>({
    get: () => (form.nominal ? form.nominal.toLocaleString('id-ID') : ''),
    set: (value: string) => {
        const angka = value.replace(/\D/g, '');
        form.nominal = angka === '' ? null : Number(angka);
        form.clearErrors('nominal');
    },
});

const submit = () => {
    form.submit(store(), {
        onSuccess: () => {
            showToast({
                message: 'Pengajuan utang berhasil dikirim',
                type: 'success',
                wordBreak: 'break-word',
            });
        },
    });
};
</script>

<template>
    <AppLayout>
        <div class="flex min-h-full flex-col bg-gray-50">
            <div class="sticky top-0 z-10 bg-white">
                <NavBar
                    title="Ajukan Utang"
                    left-arrow
                    @click-left="router.visit(index().url)"
                />
            </div>

            <form class="flex flex-col gap-4 p-4" @submit.prevent="submit">
                <CellGroup inset class="m-0!">
                    <Field
                        v-model="nominalTampil"
                        label="Nominal"
                        type="tel"
                        inputmode="numeric"
                        placeholder="0"
                        required
                        :error-message="form.errors.nominal"
                    >
                        <template #left-icon>
                            <span class="text-gray-500">Rp</span>
                        </template>
                    </Field>
                    <Field
                        v-model="form.keterangan"
                        label="Keterangan"
                        type="textarea"
                        rows="3"
                        autosize
                        maxlength="255"
                        show-word-limit
                        placeholder="Alasan pengajuan"
                        required
                        :error-message="form.errors.keterangan"
                        @update:model-value="form.clearErrors('keterangan')"
                    />
                </CellGroup>

                <p class="px-1 text-sm text-gray-500">
                    Pengajuan akan diperiksa admin. Jika disetujui, utang masuk
                    ke slip gaji bulan ini.
                </p>

                <Button
                    type="primary"
                    native-type="submit"
                    block
                    round
                    color="#fec109"
                    :loading="form.processing"
                    :disabled="form.processing"
                >
                    Kirim Pengajuan
                </Button>
            </form>
        </div>
    </AppLayout>
</template>
