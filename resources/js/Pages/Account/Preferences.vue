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
    timezones: Object,
    user: Object,
    preferences: Object,
});

// Estado das Abas
const activeTab = ref('personalize');

const tabs = [
    { id: 'personalize', name: 'preferences.tabs.personalize' },
    { id: 'password', name: 'preferences.tabs.password' },
];

// --- LOGICA DOS FORMS (Personalize Tab) ---

// 1. Configurações Gerais
const configForm = useForm({
    timezone: props.user.timezone || 'UTC',
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
                            <button 
                                v-for="tab in tabs" 
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    activeTab === tab.id
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/4 py-4 px-1 text-center border-b-2 font-medium text-sm transition-colors duration-200'
                                ]"
                            >
                                {{ $t(tab.name).toUpperCase() }}
                            </button>
                             <button class="w-1/4 py-4 px-1 text-center border-b-2 border-transparent text-gray-400 cursor-not-allowed">
                                {{ $t('preferences.tabs.sitemaps').toUpperCase() }}
                            </button>
                            <button class="w-1/4 py-4 px-1 text-center border-b-2 border-transparent text-gray-400 cursor-not-allowed">
                                {{ $t('preferences.tabs.remove_account').toUpperCase() }}
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        
                        <div v-show="activeTab === 'personalize'" class="space-y-10">
                            
                            <section>
                                <header class="mb-4 bg-indigo-50 p-3 rounded-md border-l-4 border-indigo-400">
                                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide">{{ $t('preferences.notifications.title') }}</h3>
                                </header>
                                
                                <form @change="updateNotifications" class="space-y-4 px-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $t('preferences.notifications.weekly_summary') }}</p>
                                            <p class="text-sm text-gray-500">{{ $t('preferences.notifications.weekly_summary_desc') }}</p>
                                        </div>
                                        <Toggle v-model="notifyForm.notification_preferences.weekly_summary" />
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $t('preferences.notifications.critical_alerts') }}</p>
                                            <p class="text-sm text-gray-500">{{ $t('preferences.notifications.critical_alerts_desc') }}</p>
                                        </div>
                                        <Toggle v-model="notifyForm.notification_preferences.broken_links" />
                                    </div>
                                    
                                    <div class="pt-2 flex items-center gap-4">
                                        <PrimaryButton @click.prevent="updateNotifications" :disabled="notifyForm.processing">
                                            {{ $t('preferences.notifications.save') }}
                                        </PrimaryButton>
                                        
                                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                            <p v-if="notifyForm.recentlySuccessful" class="text-sm text-gray-600">Salvo.</p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                            <SectionBorder />

                            <section>
                                <header class="mb-4 bg-indigo-50 p-3 rounded-md border-l-4 border-indigo-400">
                                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide">{{ $t('preferences.appearance.title') }}</h3>
                                </header>

                                <form @submit.prevent="updateConfig" class="space-y-6 px-2">
                                    <InputError :message="configForm.errors.ui_preferences" class="mb-2" />

                                    <div>
                                        <InputLabel :value="$t('preferences.appearance.color_scheme')" class="mb-2" />
                                        <div class="flex items-center space-x-4">
                                            <button 
                                                type="button" 
                                                @click="configForm.ui_preferences.theme = 'light'"
                                                class="flex items-center px-4 py-2 border rounded shadow-sm hover:bg-gray-50 bg-white"
                                                :class="{'ring-2 ring-indigo-500 border-indigo-500': configForm.ui_preferences.theme === 'light'}"
                                            >
                                                <span class="mr-2">☀️</span> {{ $t('preferences.appearance.light') }}
                                            </button>
                                            <button 
                                                type="button" 
                                                @click="configForm.ui_preferences.theme = 'dark'"
                                                class="flex items-center px-4 py-2 border rounded shadow-sm hover:bg-gray-700 bg-gray-600 text-white"
                                                :class="{'ring-2 ring-indigo-500 border-indigo-500': configForm.ui_preferences.theme === 'dark'}"
                                            >
                                                <span class="mr-2">🌙</span> {{ $t('preferences.appearance.dark') }}
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <InputLabel for="timezone" :value="$t('preferences.appearance.timezone')" class="mb-2" />
                                        <p class="text-xs text-gray-500 mb-2">{{ $t('preferences.appearance.timezone_desc') }}</p>
                                        <SelectInput 
                                            id="timezone" 
                                            v-model="configForm.timezone"
                                            class="w-full max-w-md"
                                        >
                                            <option value="" disabled>{{ $t('preferences.appearance.select_timezone') }}</option>
                                            <optgroup v-for="(zones, continent) in timezones" :key="continent" :label="continent">
                                                <option v-for="zone in zones" :key="zone" :value="zone">
                                                    {{ zone.replace('_', ' ') }}
                                                </option>
                                            </optgroup>
                                        </SelectInput>
                                        <InputError :message="configForm.errors.timezone" class="mt-2" />
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <PrimaryButton :disabled="configForm.processing">
                                            {{ $t('preferences.appearance.save') }}
                                        </PrimaryButton>
                                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                            <p v-if="configForm.recentlySuccessful" class="text-sm text-gray-600">Salvo.</p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                            <SectionBorder />

                            <section>
                                <header class="mb-4 bg-indigo-50 p-3 rounded-md border-l-4 border-indigo-400">
                                    <h3 class="text-sm font-bold text-indigo-900 uppercase tracking-wide">{{ $t('preferences.billing.title') }}</h3>
                                </header>
                                <p class="text-sm text-gray-500 px-2 mb-4">{{ $t('preferences.billing.desc') }} <span class="text-red-600 font-bold">{{ $t('preferences.billing.invoices') }}</span></p>

                                <form @submit.prevent="updateBilling" class="space-y-4 px-2 max-w-xl">
                                    <div>
                                        <InputLabel for="vat_number" :value="$t('preferences.billing.vat_number')" />
                                        <TextInput
                                            id="vat_number"
                                            v-model="billingForm.vat_number"
                                            type="text"
                                            class="mt-1 block w-full"
                                            :placeholder="$t('preferences.billing.vat_placeholder')"
                                        />
                                        <InputError :message="billingForm.errors.vat_number" class="mt-2" />
                                    </div>
                                    
                                    <div>
                                        <InputLabel for="billing_address" :value="$t('preferences.billing.address')" />
                                        <textarea
                                            id="billing_address"
                                            v-model="billingForm.billing_address"
                                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                            rows="4"
                                            :placeholder="$t('preferences.billing.address_placeholder')"
                                        ></textarea>
                                        <InputError :message="billingForm.errors.billing_address" class="mt-2" />
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <PrimaryButton :disabled="billingForm.processing">
                                            {{ $t('preferences.billing.save') }}
                                        </PrimaryButton>
                                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                            <p v-if="billingForm.recentlySuccessful" class="text-sm text-gray-600">Salvo.</p>
                                        </Transition>
                                    </div>
                                </form>
                            </section>

                        </div>

                        <div v-show="activeTab === 'password'" class="py-10 text-center text-gray-500">
                             <p class="mb-4">{{ $t('preferences.password.desc') }}</p>
                             <Link :href="route('profile.edit')" class="text-indigo-600 hover:underline">{{ $t('preferences.password.link') }}</Link>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>