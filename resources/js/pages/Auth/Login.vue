<script setup>
import { useForm } from '@inertiajs/vue3'
import { Field, CellGroup, Button, Checkbox } from 'vant'

const form = useForm({
    login: '',
    password: '',
    remember: false
})

const submit = () => {
    form.post('/login')
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
        <div class="w-full max-w-sm">
            <div class="bg-white rounded-2xl shadow-lg p-4 sm:p-8">
                <div class="text-center mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Login</h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">Masuk ke akun Anda</p>
                </div>

                <form @submit.prevent="submit">
                    <CellGroup inset>
                        <Field
                            v-model="form.login"
                            name="login"
                            label="Email/Username"
                            placeholder="Masukkan email atau username"
                            :error-message="form.errors.login"
                            required
                            autofocus
                        />
                        <Field
                            v-model="form.password"
                            name="password"
                            type="password"
                            label="Password"
                            placeholder="Masukkan password"
                            :error-message="form.errors.password"
                            required
                        />
                    </CellGroup>

                    <div class="mt-4 px-2">
                        <Checkbox v-model="form.remember" shape="square">Ingat saya</Checkbox>
                    </div>

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

:deep(.van-field__label) {
    font-size: 13px;
    white-space: nowrap;
    min-width: 110px;
}

:deep(.van-field__control) {
    font-size: 14px;
}

:deep(.van-cell) {
    padding: 8px 12px;
}
</style>
