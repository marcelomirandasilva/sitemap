<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

// Componentes UI
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue'; // <--- ESTAVA FALTANDO ESTE IMPORT
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Toggle from '@/Components/Toggle.vue';
import SectionBorder from '@/Components/SectionBorder.vue';

// Props
const props = defineProps({
    user: Object,
    preferences: Object,
});

// Estado das Abas
const activeTab = ref('personalize');

// Visibilidade das senhas
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const tabs = [
    { id: 'personalize', name: 'preferences.tabs.personalize' },
    { id: 'password', name: 'preferences.tabs.password' },
    { id: 'remove_account', name: 'preferences.tabs.remove_account' },
];

const confirmDeactivation = ref(false);
const deactivateForm = useForm({});

// --- LOGICA DOS FORMS (Personalize Tab) ---

// 1. Configurações Gerais
const configForm = useForm({
    ui_preferences: {
        theme: props.preferences?.theme || 'light',
    }
});

const updateConfig = () => {
    configForm.put(route('preferences.ui.update'), {
        preserveScroll: true,
    });
};

// 2. Notificações
const notifyForm = useForm({
    notification_preferences: {
        weekly_summary: props.user.notification_preferences?.weekly_summary ?? true,
        broken_links: props.user.notification_preferences?.broken_links ?? true,
    }
});

const updateNotifications = () => {
    notifyForm.put(route('preferences.notifications.update'), { preserveScroll: true });
};

// 3. Billing
const billingForm = useForm({
    billing_address: props.user.billing_address || '',
    vat_number: props.user.vat_number || '',
});

const updateBilling = () => {
    billingForm.put(route('preferences.billing.update'), { preserveScroll: true });
};

// 4. Password
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('preferences.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};

const submitDeactivation = () => {
    deactivateForm.delete(route('preferences.deactivate'), {
        onBefore: () => confirm('Tem certeza que deseja excluir sua conta permanentemente? Esta ação não pode ser desfeita.'),
    });
};
</script>

<template>

    <Head :title="$t('preferences.title')" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t('preferences.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white shadow overflow-hidden sm:rounded-lg min-h-[600px]">

                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex" aria-label="Tabs">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                                activeTab === tab.id
                                    ? 'border-primary-500 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'w-1/3 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                            ]">
                                {{ $t(tab.name).toUpperCase() }}
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">

                        <div v-show="activeTab === 'personalize'" class="space-y-10">

                            <section>
                                <header class="mb-4 bg-primary-50 p-3 rounded-md border-l-4 border-primary-400">
                                    <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">{{
                                        $t('preferences.notifications.title') }}</h3>
                                </header>

                                <form @change="updateNotifications" class="space-y-4 px-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-500">{{
                                                $t('preferences.notifications.weekly_summary_desc') }}</p>
                                        </div>
                                        <Toggle v-model="notifyForm.notification_preferences.weekly_summary" />
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm text-gray-500">{{
                                                $t('preferences.notifications.critical_alerts_desc') }}</p>
                                        </div>
                                        <Toggle v-model="notifyForm.notification_preferences.broken_links" />
                                    </div>

                                    <div class="pt-2 flex items-center gap-4">
                                        <PrimaryButton @click.prevent="updateNotifications"
                                            :disabled="notifyForm.processing">
                                            {{ $t('preferences.notifications.save') }}
                                        </PrimaryButton>

                                        <Transition enter-active-class="transition ease-in-out"
                                            enter-from-class="opacity-0" leave-active-class="transition ease-in-out"
                                            leave-to-class="opacity-0">
                                            <p v-if="notifyForm.recentlySuccessful" class="text-sm text-gray-600">Salvo.
                                            </p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                            <SectionBorder />

                            <section>
                                <header class="mb-4 bg-primary-50 p-3 rounded-md border-l-4 border-primary-400">
                                    <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">{{
                                        $t('preferences.appearance.title') }}</h3>
                                </header>

                                <form @submit.prevent="updateConfig" class="space-y-6 px-2">
                                    <InputError :message="configForm.errors.ui_preferences" class="mb-2" />

                                    <div>
                                        <InputLabel :value="$t('preferences.appearance.color_scheme')" class="mb-2" />
                                        <div class="flex items-center space-x-4">
                                            <button type="button" @click="configForm.ui_preferences.theme = 'light'"
                                                class="flex items-center px-4 py-2 border rounded shadow-sm hover:bg-gray-50 bg-white"
                                                :class="{ 'ring-2 ring-primary-500 border-primary-500': configForm.ui_preferences.theme === 'light' }">
                                                <span class="mr-2">☀️</span> {{ $t('preferences.appearance.light') }}
                                            </button>
                                            <button type="button" @click="configForm.ui_preferences.theme = 'dark'"
                                                class="flex items-center px-4 py-2 border rounded shadow-sm hover:bg-gray-700 bg-gray-600 text-white"
                                                :class="{ 'ring-2 ring-primary-500 border-primary-500': configForm.ui_preferences.theme === 'dark' }">
                                                <span class="mr-2">🌙</span> {{ $t('preferences.appearance.dark') }}
                                            </button>
                                        </div>
                                    </div>



                                    <div class="flex items-center gap-4">
                                        <PrimaryButton :disabled="configForm.processing">
                                            {{ $t('preferences.appearance.save') }}
                                        </PrimaryButton>
                                        <Transition enter-active-class="transition ease-in-out"
                                            enter-from-class="opacity-0" leave-active-class="transition ease-in-out"
                                            leave-to-class="opacity-0">
                                            <p v-if="configForm.recentlySuccessful" class="text-sm text-gray-600">Salvo.
                                            </p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                            <SectionBorder />

                            <section>
                                <header class="mb-4 bg-primary-50 p-3 rounded-md border-l-4 border-primary-400">
                                    <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">{{
                                        $t('preferences.billing.title') }}</h3>
                                </header>
                                <p class="text-sm text-gray-500 px-2 mb-4">{{ $t('preferences.billing.desc') }} <span
                                        class="text-danger-600 font-bold">{{ $t('preferences.billing.invoices')
                                        }}</span></p>

                                <form @submit.prevent="updateBilling" class="space-y-4 px-2 max-w-xl">
                                    <div>
                                        <InputLabel for="vat_number" :value="$t('preferences.billing.vat_number')" />
                                        <TextInput id="vat_number" v-model="billingForm.vat_number" type="text"
                                            class="mt-1 block w-full"
                                            :placeholder="$t('preferences.billing.vat_placeholder')" />
                                        <InputError :message="billingForm.errors.vat_number" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="billing_address" :value="$t('preferences.billing.address')" />
                                        <textarea id="billing_address" v-model="billingForm.billing_address"
                                            class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                            rows="4"
                                            :placeholder="$t('preferences.billing.address_placeholder')"></textarea>
                                        <InputError :message="billingForm.errors.billing_address" class="mt-2" />
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <PrimaryButton :disabled="billingForm.processing">
                                            {{ $t('preferences.billing.save') }}
                                        </PrimaryButton>
                                        <Transition enter-active-class="transition ease-in-out"
                                            enter-from-class="opacity-0" leave-active-class="transition ease-in-out"
                                            leave-to-class="opacity-0">
                                            <p v-if="billingForm.recentlySuccessful" class="text-sm text-gray-600">
                                                Salvo.</p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                        </div>

                        <div v-show="activeTab === 'password'" class="p-6 space-y-6">
                            <header class="mb-6 bg-primary-50 p-4 rounded-md border-l-4 border-primary-400">
                                <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">
                                    {{ $t('preferences.password.title') }}
                                </h3>
                            </header>

                            <form @submit.prevent="updatePassword" class="space-y-6 max-w-2xl">
                                <div class="max-w-md">
                                    <InputLabel for="current_password" :value="$t('preferences.password.current_password')" />
                                    <div class="relative mt-1">
                                        <TextInput
                                            id="current_password"
                                            v-model="passwordForm.current_password"
                                            :type="showCurrentPassword ? 'text' : 'password'"
                                            class="block w-full pr-10"
                                            autocomplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            @click="showCurrentPassword = !showCurrentPassword"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                        >
                                            <svg v-if="!showCurrentPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.61 3.049 9.964 6.678.082.233.082.467 0 .7a10.455 10.455 0 01-9.964 6.678c-4.789 0-8.61-3.049-9.964-6.678z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.822 7.822L21 21m-2.228-2.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                            </svg>
                                        </button>
                                    </div>
                                    <InputError :message="passwordForm.errors.current_password" class="mt-2" />
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="password" :value="$t('preferences.password.new_password')" />
                                        <div class="relative mt-1">
                                            <TextInput
                                                id="password"
                                                v-model="passwordForm.password"
                                                :type="showNewPassword ? 'text' : 'password'"
                                                class="block w-full pr-10"
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                @click="showNewPassword = !showNewPassword"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                            >
                                                <svg v-if="!showNewPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.61 3.049 9.964 6.678.082.233.082.467 0 .7a10.455 10.455 0 01-9.964 6.678c-4.789 0-8.61-3.049-9.964-6.678z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.822 7.822L21 21m-2.228-2.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                            </button>
                                        </div>
                                        <InputError :message="passwordForm.errors.password" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="password_confirmation" :value="$t('preferences.password.confirm_password')" />
                                        <div class="relative mt-1">
                                            <TextInput
                                                id="password_confirmation"
                                                v-model="passwordForm.password_confirmation"
                                                :type="showConfirmPassword ? 'text' : 'password'"
                                                class="block w-full pr-10"
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                @click="showConfirmPassword = !showConfirmPassword"
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none"
                                            >
                                                <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.644C3.399 8.049 7.21 5 12 5c4.789 0 8.61 3.049 9.964 6.678.082.233.082.467 0 .7a10.455 10.455 0 01-9.964 6.678c-4.789 0-8.61-3.049-9.964-6.678z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.822 7.822L21 21m-2.228-2.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                                </svg>
                                            </button>
                                        </div>
                                        <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500">{{ $t('preferences.password.help_text') }}</p>

                                <div class="flex items-center gap-4">
                                    <PrimaryButton :disabled="passwordForm.processing" class="py-3 px-8">
                                        {{ $t('preferences.password.submit') }}
                                    </PrimaryButton>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <p v-if="passwordForm.recentlySuccessful" class="text-sm text-gray-600">
                                            {{ $t('preferences.saved') }}
                                        </p>
                                    </Transition>
                                </div>
                            </form>
                        </div>

                        <div v-show="activeTab === 'remove_account'" class="p-6 space-y-6">
                            <div v-if="!confirmDeactivation">
                                <header class="mb-6 bg-primary-50 p-4 rounded-md border-l-4 border-primary-400">
                                    <h3 class="text-sm font-bold text-primary-900 uppercase tracking-wide">
                                        {{ $t('preferences.deactivate.title') }}
                                    </h3>
                                </header>
                                <p class="text-sm text-gray-600">
                                    {{ $t('preferences.deactivate.help_text') }}
                                    <a href="#" @click.prevent="confirmDeactivation = true" class="text-danger-600 font-bold hover:underline">
                                        {{ $t('preferences.deactivate.click_here') }}
                                    </a>.
                                </p>
                            </div>

                            <div v-else class="space-y-6">
                                <header class="mb-6 bg-gray-100 p-4 rounded-md border-b flex items-center gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide">
                                        {{ $t('preferences.deactivate.title') }}
                                    </h3>
                                </header>

                                <h4 class="text-xl text-gray-800">{{ $t('preferences.deactivate.title') }}</h4>

                                <div class="bg-primary-50 text-primary-900 p-4 rounded border border-primary-100 text-sm">
                                    {{ $t('preferences.deactivate.info_box') }}
                                </div>

                                <div class="bg-danger-600 text-white p-4 rounded shadow-sm font-medium text-sm">
                                    {{ $t('preferences.deactivate.warning_box') }}
                                </div>

                                <button
                                    @click="submitDeactivation"
                                    :disabled="deactivateForm.processing"
                                    class="bg-danger-600 hover:bg-danger-700 text-white font-bold py-3 px-6 rounded uppercase tracking-wider text-sm transition-colors shadow-sm disabled:opacity-50"
                                >
                                    {{ $t('preferences.deactivate.button') }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>