<script setup>
import { useForm } from '@inertiajs/vue3';
import { Field, CellGroup, Button } from 'vant';

const form = useForm({
    login: '',
    password: '',
});

const submit = () => {
    form.post('/login');
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 p-4">
        <div class="w-full max-w-sm">
            <div class="rounded-2xl bg-white p-4 shadow-lg sm:p-8">
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-bold text-gray-900 sm:text-2xl">
                        Login
                    </h2>
                    <p class="mt-1 text-xs text-gray-600 sm:text-sm">
                        Masuk ke akun Anda
                    </p>
                </div>

                <form @submit.prevent="submit">
                    <CellGroup inset>
                        <Field
                            v-model="form.login"
                            name="login"
                            placeholder="Masukkan email atau username"
                            :error-message="form.errors.login"
                            required
                            autofocus
                        />
                        <Field
                            v-model="form.password"
                            name="password"
                            type="password"
                            placeholder="Masukkan password"
                            :error-message="form.errors.password"
                            required
                        />
                    </CellGroup>

                    <div class="mt-6">
                        <Button
                            type="submit"
                            color="#fec109"
                            block
                            round
                            :loading="form.processing"
                            :disabled="form.processing"
                            native-type="submit"
                        >
                            {{ form.processing ? 'Memproses...' : 'Login' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.van-cell-group--inset) {
    margin: 0;
}

:deep(.van-field__control) {
    font-size: 14px;
}

:deep(.van-cell) {
    padding: 8px 12px;
}
</style>
