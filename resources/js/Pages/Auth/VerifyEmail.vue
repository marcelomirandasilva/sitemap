<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <AppLayout title="Email Verification">
        <div class="flex flex-col items-center pt-8 px-4 pb-20">
            <!-- Caixa Branca de Reenvio -->
            <div class="w-full max-w-3xl bg-white border border-gray-200 rounded-sm shadow-sm pb-8">
                <div class="bg-[#f5f5f5] border-b border-gray-200 py-3 text-center text-sm font-bold text-[#555] uppercase flex items-center justify-center gap-2 mb-6">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
                    {{ $t('auth.verify.resend_title') }}
                </div>
                
                <div class="px-6 md:px-10 text-[#555] text-[15px] leading-relaxed">
                    <p class="mb-4">
                        {{ $t('auth.verify.check_email') }} <strong class="text-[#333]">{{ user.email }}</strong>
                    </p>
                    
                    <p class="mb-8">
                        {{ $t('auth.verify.click_button') }}
                    </p>
                    
                    <PrimaryButton @click="submit" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="!bg-[#008cba] hover:!bg-[#007ba6] !rounded-sm font-bold uppercase py-3 px-6 shadow border border-[#007ba6]">
                        {{ $t('auth.verify.send_link') }}
                    </PrimaryButton>

                    <div v-if="verificationLinkSent" class="mt-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-sm text-sm">
                        {{ $t('auth.verify.link_sent') }}
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
