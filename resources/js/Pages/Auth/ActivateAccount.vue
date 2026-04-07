<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('activate.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Ativação de Conta" />

        <div class="mb-8 text-center border-b border-gray-100 pb-4">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center justify-center gap-3">
                <svg class="w-7 h-7 text-accent-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                Ativação de Conta
            </h2>
            <div class="mt-2 text-sm text-gray-500 font-medium">
                Defina sua senha para concluir a ativação da sua conta.
            </div>
        </div>
        
        <div class="bg-gray-50 text-gray-600 px-4 py-3 rounded-md mb-8 text-sm border border-gray-200 text-center shadow-inner">
            Seu endereço verificado é: <strong class="text-accent-800">{{ email }}</strong>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Sua senha" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirme sua senha" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>
            
            <div class="text-xs text-gray-500 mt-2">
                A senha deve ter pelo menos 8 caracteres
            </div>

            <div class="mt-6 flex items-center justify-end">
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Ativar Conta
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
