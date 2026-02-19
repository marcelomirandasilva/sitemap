<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { loadLanguageAsync } from 'laravel-vue-i18n';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    defaultTab: {
        type: String,
        default: 'signup'
    },
    plans: Array // Planos injetados via Inertia
});

const activeTab = ref(props.defaultTab || 'signup');
const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();
const currentCurrency = ref('USD'); // Padrão USD

const mudarIdioma = (lang) => {
    localStorage.setItem('user_locale', lang);
    loadLanguageAsync(lang);
    
    // Atualiza moeda baseada no idioma
    if (lang === 'pt') {
        currentCurrency.value = 'BRL';
    } else {
        currentCurrency.value = 'USD';
    }
};

onMounted(() => {
    const savedLocale = localStorage.getItem('user_locale');
    if (savedLocale) {
        loadLanguageAsync(savedLocale);
        if (savedLocale === 'pt') {
            currentCurrency.value = 'BRL';
        }
    }
});

// Helpers de Preço
const getMonthlyPrice = (plan) => {
    return currentCurrency.value === 'BRL' ? plan.price_monthly_brl : plan.price_monthly_usd;
};

const getYearlyPrice = (plan) => {
    return currentCurrency.value === 'BRL' ? plan.price_yearly_brl : plan.price_yearly_usd;
};

const formatPrice = (plan, type) => {
    const locale = currentCurrency.value === 'BRL' ? 'pt-BR' : 'en-US';
    const currency = currentCurrency.value;
    
    let value = 0;
    
    if (type === 'monthly') {
        value = getMonthlyPrice(plan);
    } else if (type === 'yearly_monthly') {
        // Preço anual dividido por 12
        value = getYearlyPrice(plan) / 12;
    }

    // Se valor for 0
    if (value <= 0) {
        return currentCurrency.value === 'BRL' ? 'R$ 0' : '$0';
    }

    // Formata (dividindo por 100 pois vem em centavos do DB se for int,
    // mas no Seeder atual parece estar salvando em centavos)
    // Model Plan.php tem casts para integer.
    // O seeder salvou: price_monthly_usd = 599 => $5.99
    // Entao dividimos por 100.
    
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: currency
    }).format(value / 100);
};

// --- LÓGICA DE REGISTRO (SIGNUP) ---
const registerForm = useForm({
    name: '',
    url: '', // Campo URL Adicionado
    email: '',
    // Password removido (gerado automaticamente)
    terms: false,
});

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset(),
    });
};

// --- LÓGICA DE LOGIN ---
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};
</script>

<template>
    <Head title="Gerador de Sitemap XML" />

    <div class="min-h-screen bg-[#f5f5f5] font-sans text-gray-700">
        <!-- Flash Message -->
        <div v-if="$page.props.flash.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 fixed top-0 right-0 m-4 z-50 shadow-md rounded" role="alert">
            <p class="font-bold">Sucesso!</p>
            <p>{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash.error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 fixed top-0 right-0 m-4 z-50 shadow-md rounded" role="alert">
            <p class="font-bold">Erro!</p>
            <p>{{ $page.props.flash.error }}</p>
        </div>
        <!-- Header Simplificado estilizado -->
        <div class="relative bg-white border-b border-gray-200">
             <header class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                <!-- Logo / Brand -->
                 <div class="flex items-center gap-2">
                        <div class="text-[#a4332b]">
                            <!-- Ícone Simplificado -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 16h4v4H4v-4zm0-6h4v4H4v-4zm0-6h4v4H4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4z" />
                            </svg>
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-gray-800">
                            {{ appName }}
                        </span>
                 </div>

                 <!-- Nav Links -->
                  <nav class="hidden md:flex space-x-6 text-sm font-medium text-gray-600 items-center">
                        <div class="flex items-center gap-2 mr-4">
                             <span class="text-[#a4332b] font-bold text-xs flex items-center gap-1 cursor-pointer">
                                🛑 SUPPORT
                             </span>
                              <span class="text-[#a4332b] font-bold text-xs flex items-center gap-1 cursor-pointer">
                                📋 HELP
                             </span>
                        </div>
                         <!-- Lang Switch -->
                        <div class="flex items-center gap-2">
                            <button @click="mudarIdioma('pt')" class="hover:opacity-80 transition"><img src="/flags/br.svg" class="w-5 shadow-sm" /></button>
                            <button @click="mudarIdioma('en')" class="hover:opacity-80 transition"><img src="/flags/us.svg" class="w-5 shadow-sm" /></button>
                        </div>
                  </nav>
             </header>
        </div>

        <!-- Hero Section (Azul Claro) -->
        <div class="bg-[#cfe8f9] pb-16 pt-10 text-center">
            
            <h1 class="text-2xl md:text-3xl text-gray-600 font-normal mb-10">
                {{ $t('hero.main_title') }}
            </h1>

            <div class="max-w-3xl mx-auto px-4">
                <!-- Card Container -->
                <div class="bg-white rounded shadow-sm max-w-2xl mx-auto overflow-hidden">
                    
                    <!-- Tabs -->
                    <div class="flex text-sm font-bold tracking-wide uppercase cursor-pointer">
                         <div @click="activeTab = 'signup'" :class="[
                            'flex-1 py-4 transition-colors duration-200 border-t-2',
                            activeTab === 'signup'
                                ? 'bg-white text-[#a4332b] border-[#a4332b]'
                                : 'bg-[#64b5f6] text-white border-[#64b5f6] hover:bg-[#42a5f5]'
                        ]">
                            {{ $t('auth.signup_tab') }}
                        </div>
                        <div @click="activeTab = 'login'" :class="[
                            'flex-1 py-4 transition-colors duration-200 border-t-2',
                            activeTab === 'login'
                                ? 'bg-white text-[#a4332b] border-[#a4332b]'
                                : 'bg-[#64b5f6] text-white border-[#64b5f6] hover:bg-[#42a5f5]'
                        ]">
                            {{ $t('auth.login_tab') }}
                        </div>
                    </div>

                    <!-- Conteúdo do Form -->
                    <div class="p-8 md:p-12">
                        
                         <div v-if="activeTab === 'signup'" class="space-y-6">
                            <h2 class="text-xl text-gray-500 font-light mb-8">
                                {{ $t('hero.subtitle') }}
                            </h2>

                            <form @submit.prevent="submitRegister" class="space-y-5 text-left max-w-lg mx-auto">
                                
                                <!-- URL Input -->
                                <div>
                                    <input v-model="registerForm.url" type="url" required
                                        :placeholder="'* ' + $t('form.url_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-600 placeholder-gray-400 focus:ring-0 focus:border-blue-400 transition bg-transparent text-sm">
                                     <div v-if="registerForm.errors.url" class="text-red-500 text-xs mt-1">{{ registerForm.errors.url }}</div>
                                </div>
                                

                                

                                <!-- Email Input -->
                                <div>
                                    <input v-model="registerForm.email" type="email" required
                                        :placeholder="$t('form.email_create_account')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-600 placeholder-gray-400 focus:ring-0 focus:border-blue-400 transition bg-transparent text-sm">
                                    <div v-if="registerForm.errors.email" class="text-red-500 text-xs mt-1">{{ registerForm.errors.email }}</div>
                                </div>

                                <!-- Name & Password (Mantidos para compatibilidade, mas compactos) -->
                                <!-- Name (Mantidos para compatibilidade, mas compactos) -->
                                 <div>
                                    <input v-model="registerForm.name" type="text" required
                                        :placeholder="$t('form.name_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-600 placeholder-gray-400 focus:ring-0 focus:border-blue-400 transition bg-transparent text-sm">
                                </div>

                                <!-- Checkbox -->
                                <div class="flex items-start gap-2 mt-4">
                                    <input v-model="registerForm.terms" id="terms" type="checkbox" required
                                        class="mt-1 w-4 h-4 text-[#a4332b] border-gray-300 rounded focus:ring-[#a4332b]">
                                    <label for="terms" class="text-xs text-gray-500 italic">
                                        * {{ $t('auth.agree_prefix') }} <a href="#" class="text-[#a4332b] hover:underline">{{ $t('auth.privacy_policy') }}</a> 
                                        {{ $t('auth.and') }} <a href="#" class="text-[#a4332b] hover:underline">{{ $t('auth.terms_of_use') }}</a> {{ $t('auth.service_suffix') }}
                                    </label>
                                </div>

                                <!-- Button -->
                                <div class="text-center pt-2">
                                     <button :disabled="registerForm.processing"
                                        class="bg-[#a54c44] hover:bg-[#8f3b34] text-white text-sm font-bold py-3 px-8 rounded shadow uppercase tracking-wide transition disabled:opacity-50">
                                        {{ registerForm.processing ? 'PROCESING...' : $t('hero.cta') }}
                                    </button>
                                </div>
                            </form>
                            
                            <div class="relative py-4">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="bg-white px-4 text-sm text-gray-500 uppercase">{{ $t('auth.or') }}</span>
                                </div>
                            </div>
                            <a :href="route('auth.google')" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-600 font-medium py-2 px-6 rounded-full flex items-center justify-center gap-3 mx-auto transition bg-white shadow-sm cursor-pointer no-underline">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.26.81-.58z" fill="#FBBC05" />
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                                </svg>
                                {{ $t('auth.google') }}
                            </a>
                        </div>

                        <div v-if="activeTab === 'login'" class="space-y-6">
                            <h2 class="text-xl text-gray-600 font-light mb-6">
                                {{ $t('auth.login_subtitle') }}
                            </h2>

                            <form @submit.prevent="submitLogin" class="space-y-4 text-left">
                                <div>
                                    <input v-model="loginForm.email" type="email" 
                                        :placeholder="'* ' + $t('auth.email_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    <div v-if="loginForm.errors.email" class="text-red-500 text-xs mt-1">{{ loginForm.errors.email }}</div>
                                </div>
                                <div>
                                    <input v-model="loginForm.password" type="password" 
                                        :placeholder="'* ' + $t('auth.password_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    <div v-if="loginForm.errors.password" class="text-red-500 text-xs mt-1">{{ loginForm.errors.password }}</div>
                                </div>

                                <div class="text-center mt-6">
                                    <button :disabled="loginForm.processing"
                                        class="w-full sm:w-auto bg-[#a4332b] hover:bg-[#8f2c25] text-white font-bold py-3 px-12 rounded shadow-md uppercase tracking-wide transition disabled:opacity-50">
                                        {{ loginForm.processing ? '...' : $t('auth.login_tab').toUpperCase() }}
                                    </button>
                                    <div class="mt-4">
                                        <a href="#" class="text-[#a4332b] font-bold hover:underline text-sm border-b border-[#a4332b] border-dashed pb-0.5">
                                            {{ $t('auth.forgot_password') }}
                                        </a>
                                    </div>
                                </div>
                            </form>
                             <div class="relative py-4">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-gray-300"></div>
                                </div>
                                <div class="relative flex justify-center">
                                    <span class="bg-white px-4 text-sm text-gray-500 uppercase">{{ $t('auth.or') }}</span>
                                </div>
                            </div>
                            <a :href="route('auth.google')" class="w-full sm:w-auto border border-gray-300 hover:bg-gray-50 text-gray-600 font-medium py-2 px-6 rounded-full flex items-center justify-center gap-3 mx-auto transition bg-white shadow-sm cursor-pointer no-underline">
                                <svg class="w-5 h-5" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.26.81-.58z" fill="#FBBC05" />
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                                </svg>
                                {{ $t('auth.google') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Tabela de Preços Dinâmica (Estilo "Visão Geral") -->
            <div class="max-w-6xl mx-auto px-4 mt-8 pb-16">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-left border-l-4 border-[#a4332b] pl-3">
                    {{ $t('pricing.overview_title') || 'Visão geral dos planos' }}
                </h2>
                
                <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-[#0f172a] text-white">
                            <tr>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider">{{ $t('pricing.table.plan') }}</th>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider text-center">{{ $t('pricing.table.monthly') }}</th>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider text-center">{{ $t('pricing.table.yearly') }}</th>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider text-center">{{ $t('pricing.table.limit') }}</th>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider">{{ $t('pricing.table.update') }}</th>
                                <th class="py-4 px-6 font-bold uppercase tracking-wider">{{ $t('pricing.table.ideal') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(plan, index) in plans" :key="plan.id" class="hover:bg-gray-50 transition-colors">
                                <!-- Nome -->
                                <td class="py-4 px-6 font-medium text-gray-900">{{ plan.name }}</td>
                                
                                <!-- Preço Mensal -->
                                <td class="py-4 px-6 text-center text-gray-700 font-semibold">
                                    {{ formatPrice(plan, 'monthly') }}
                                </td>

                                <!-- Preço Anual (Dividido por 12) -->
                                <td class="py-4 px-6 text-center text-gray-700">
                                    <span v-if="getYearlyPrice(plan) > 0" class="font-bold text-[#a4332b]">
                                        {{ formatPrice(plan, 'yearly_monthly') }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>

                                <!-- Limite URLs -->
                                <td class="py-4 px-6 text-center font-medium text-gray-800">
                                    {{ new Intl.NumberFormat('en-US').format(plan.max_pages) }}
                                </td>

                                <!-- Frequência -->
                                <td class="py-4 px-6 text-gray-600">
                                    {{ $t(`pricing.plans.${plan.slug}.frequency`) }}
                                </td>

                                <!-- Ideal Para -->
                                <td class="py-4 px-6 text-gray-600 italic">
                                    {{ $t(`pricing.plans.${plan.slug}.ideal_for`) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        <footer class="border-t border-gray-200 bg-white pt-10 pb-6">
            <div class="max-w-6xl mx-auto px-4 text-center">
                <nav class="flex flex-wrap justify-center gap-6 mb-6 text-sm text-[#a4332b] font-medium">
                    <a href="#" class="hover:underline">{{ $t('footer.privacy') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.terms') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.api') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.contact') }}</a>
                    <a href="#" class="hover:underline">{{ $t('footer.help') }}</a>
                </nav>
                <p class="text-xs text-gray-400">
                    &copy; 2005-{{ anoAtual }} {{ $page.props.appName }}. Todos os direitos reservados.
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    SyNesis Tecnologia.
                </p>
            </div>
        </footer>
    </div>
</template>