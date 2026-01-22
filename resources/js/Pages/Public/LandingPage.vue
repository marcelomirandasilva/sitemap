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
    }
});

const activeTab = ref(props.defaultTab || 'signup');
const appName = import.meta.env.VITE_APP_NAME;
const anoAtual = new Date().getFullYear();

const mudarIdioma = (lang) => {
    localStorage.setItem('user_locale', lang);
    loadLanguageAsync(lang);
};

onMounted(() => {
    const savedLocale = localStorage.getItem('user_locale');
    if (savedLocale) {
        loadLanguageAsync(savedLocale);
    }
});

// --- LÓGICA DE REGISTRO (SIGNUP) ---
// Removemos o campo 'url' daqui
const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
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
        <div class="relative bg-gradient-to-b from-[#e8f4fc] to-[#f5f5f5] border-b border-gray-200">

            <header class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex items-center gap-2">
                        <div class="text-[#a4332b]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 16h4v4H4v-4zm0-6h4v4H4v-4zm0-6h4v4H4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4zm6 12h4v4h-4v-4zm0-6h4v4h-4v-4zm0-6h4v4h-4v-4z" />
                                <path d="M2 2h20v20H2V2zm2 2v16h16V4H4z" fill="none" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-2xl font-bold leading-none tracking-tight text-gray-800">
                                {{ appName }}
                            </span>
                            <span class="text-xs text-gray-500 tracking-wider">{{ $t('hero.subtitle_tag') }}</span>
                        </div>
                    </div>

                    <nav class="hidden md:flex space-x-6 text-sm font-medium text-gray-600 items-center">
                        <a href="#" class="hover:text-[#a4332b] transition">{{ $t('nav.support') }}</a>
                        <a href="#" class="hover:text-[#a4332b] transition">{{ $t('nav.help') }}</a>
                        <template v-if="$page.props.auth.user">
                            <Link :href="route('dashboard')" class="text-[#a4332b] hover:underline">
                                Dashboard
                            </Link>
                        </template>
                        <div class="flex items-center gap-2 mr-2">
                            <button @click="mudarIdioma('pt')" class="hover:scale-110 transition-transform cursor-pointer" title="Português">
                                <img src="/flags/br.svg" alt="Português" class="w-6 h-auto shadow-sm rounded-sm" />
                            </button>
                            <button @click="mudarIdioma('en')" class="hover:scale-110 transition-transform cursor-pointer" title="English">
                                <img src="/flags/us.svg" alt="English" class="w-6 h-auto shadow-sm rounded-sm" />
                            </button>
                        </div>
                    </nav>
                </div>
            </header>

            <div class="max-w-3xl mx-auto px-4 py-12 text-center">
                <div class="bg-white rounded-lg shadow-xl overflow-hidden max-w-2xl mx-auto mt-4">
                    <div class="flex text-lg font-bold tracking-wide uppercase cursor-pointer">
                        <div @click="activeTab = 'signup'" :class="[
                            'flex-1 py-4 transition-colors duration-200',
                            activeTab === 'signup'
                                ? 'bg-white text-[#a4332b] border-t-2 border-[#a4332b]'
                                : 'bg-[#64b5f6] text-white hover:bg-[#42a5f5]'
                        ]">
                            {{ $t('auth.signup_tab') }}
                        </div>
                        <div @click="activeTab = 'login'" :class="[
                            'flex-1 py-4 transition-colors duration-200',
                            activeTab === 'login'
                                ? 'bg-white text-[#a4332b] border-t-2 border-[#a4332b]'
                                : 'bg-[#64b5f6] text-white hover:bg-[#42a5f5]'
                        ]">
                            {{ $t('auth.login_tab') }}
                        </div>
                    </div>

                    <div class="p-8 md:p-10">

                        <div v-if="activeTab === 'signup'" class="space-y-6">
                            <h2 class="text-xl text-gray-600 font-light mb-6">
                                {{ $t('hero.subtitle') }}
                            </h2>

                            <form @submit.prevent="submitRegister" class="space-y-4 text-left">
                                
                                <div>
                                    <input v-model="registerForm.name" type="text" required
                                        :placeholder="$t('form.name_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    <div v-if="registerForm.errors.name" class="text-red-500 text-xs mt-1">{{ registerForm.errors.name }}</div>
                                </div>

                                <div>
                                    <input v-model="registerForm.email" type="email" required
                                        :placeholder="'* ' + $t('form.email_placeholder')"
                                        class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    <div v-if="registerForm.errors.email" class="text-red-500 text-xs mt-1">{{ registerForm.errors.email }}</div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <input v-model="registerForm.password" type="password" required
                                            :placeholder="$t('form.password_placeholder')"
                                            class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    </div>
                                    <div>
                                        <input v-model="registerForm.password_confirmation" type="password" required
                                            :placeholder="$t('form.confirm_password_placeholder')"
                                            class="w-full border-0 border-b border-gray-300 px-0 py-2 text-gray-700 placeholder-gray-400 focus:ring-0 focus:border-blue-500 transition">
                                    </div>
                                </div>
                                <div v-if="registerForm.errors.password" class="text-red-500 text-xs mt-1">{{ registerForm.errors.password }}</div>

                                <div class="flex items-start gap-3 mt-4">
                                    <input v-model="registerForm.terms" id="terms" type="checkbox" required
                                        class="mt-1 w-5 h-5 text-[#a4332b] border-gray-300 rounded focus:ring-[#a4332b]">
                                    <label for="terms" class="text-sm text-gray-500">
                                        * {{ $t('auth.agree_prefix') }} <a href="#" class="text-[#a4332b] font-bold hover:underline">{{ $t('auth.privacy_policy') }}</a> 
                                        {{ $t('auth.and') }} <a href="#" class="text-[#a4332b] font-bold hover:underline">{{ $t('auth.terms_of_use') }}</a> {{ $t('auth.service_suffix') }}
                                    </label>
                                </div>

                                <button :disabled="registerForm.processing"
                                    class="w-full sm:w-auto bg-[#a4332b] hover:bg-[#8f2c25] text-white font-bold py-3 px-12 rounded shadow-md uppercase tracking-wide mt-4 transition disabled:opacity-50">
                                    {{ registerForm.processing ? '...' : $t('hero.cta') }}
                                </button>
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

        <div class="max-w-5xl mx-auto px-4 py-16">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200">
                    <div class="p-6 bg-gray-50 md:col-span-1 flex flex-col justify-center">
                        <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $t('pricing.features') }}</h3>
                        <p class="text-sm text-gray-500">{{ $t('pricing.compare') }}</p>
                    </div>
                    <div class="p-6 text-center">
                        <h3 class="text-2xl font-bold text-green-600 mb-1">{{ $t('pricing.free') }}</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ $t('pricing.small_sites') }}</p>
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition text-sm mb-6">
                            {{ $t('pricing.start_now') }}
                        </button>
                    </div>
                    <div class="p-6 text-center bg-blue-50/30">
                        <h3 class="text-2xl font-bold text-[#a4332b] mb-1">{{ $t('pricing.pro') }}</h3>
                        <p class="text-sm text-gray-500 mb-6">{{ $t('pricing.pro_price') }}</p>
                        <button class="w-full bg-[#a4332b] hover:bg-[#8f2c25] text-white font-bold py-2 px-4 rounded transition text-sm mb-6">
                            {{ $t('pricing.view_plans') }}
                        </button>
                    </div>
                </div>

                <div class="border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.xml_standard') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-green-500 text-xl">✔</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.async_crawling') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-green-500 text-xl">✔</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.broken_links') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-green-500 text-xl">✔</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.image_sitemaps') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-gray-300 text-sm">✖</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.video_sitemaps') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-gray-300 text-sm">✖</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.news_sitemaps') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-gray-300 text-sm">✖</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-200 hover:bg-gray-50 transition">
                        <div class="p-3 px-6 text-sm font-medium text-gray-700 flex items-center">{{ $t('features.api_access') }}</div>
                        <div class="p-3 flex justify-center items-center"><span class="text-gray-300 text-sm">✖</span></div>
                        <div class="p-3 flex justify-center items-center bg-blue-50/30"><span class="text-green-500 text-xl">✔</span></div>
                    </div>
                </div>
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