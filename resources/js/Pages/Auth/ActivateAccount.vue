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
        <Head title="Activate Your Account" />

        <div class="mb-6 text-center">
            <h2 class="text-xl font-bold text-gray-700 mb-2 flex items-center justify-center gap-2">
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg>
                Activate Your Account
            </h2>
        </div>

        <div class="bg-white border border-gray-200 p-6 md:p-8 rounded-sm shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg text-gray-600 font-medium mb-4">Define your password to complete account activation</h3>
                
                <div class="bg-[#f0f7f9] text-[#334155] px-4 py-3 rounded-sm mb-6 text-sm">
                    Your account email address is: <strong>{{ email }}</strong>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-2">
                    <div>
                        <InputLabel for="password" value="Select your password" class="!text-sm !text-gray-600 mb-1" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full !rounded-sm !border-gray-300 shadow-sm focus:border-[#00aced] focus:ring-[#00aced]"
                            v-model="form.password"
                            required
                            autofocus
                            autocomplete="new-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Repeat password to confirm" class="!text-sm !text-gray-600 mb-1" />

                        <TextInput
                            id="password_confirmation"
                            type="password"
                            class="mt-1 block w-full !rounded-sm !border-gray-300 shadow-sm focus:border-[#00aced] focus:ring-[#00aced]"
                            v-model="form.password_confirmation"
                            required
                            autocomplete="new-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>
                </div>
                
                <div class="text-xs text-gray-500 mb-6">
                    The password must be at least 8 characters long
                </div>

                <div class="flex items-center">
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="!bg-[#008cba] hover:!bg-[#007ba6] !rounded-sm font-bold uppercase py-3 px-6 shadow border border-[#007ba6]">
                        Activate Account
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
