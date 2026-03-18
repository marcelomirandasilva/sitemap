<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    invoices: Array,
    activeSubscription: Object,
});
</script>

<template>
    <Head :title="$t('billing.history.title')" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t('billing.history.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- SUAS ASSINATURAS CARD -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="bg-gray-50 border-b border-gray-200 px-6 py-3 flex justify-between items-center">
                        <div class="flex items-center gap-2 text-sm font-bold text-gray-600 tracking-wide uppercase mx-auto">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            {{ $t('billing.subscriptions.title') }}
                        </div>
                        <a href="#" class="inline-flex items-center gap-1 bg-[#fbf9c8] text-gray-700 border border-gray-300 rounded px-3 py-1 text-xs hover:bg-[#f6f3a6] transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            {{ $t('billing.subscriptions.help') }}
                        </a>
                    </div>
                    
                    <div class="p-8">
                        <!-- Estado Vazio -->
                        <div v-if="!activeSubscription" class="text-2xl font-light text-gray-600">
                            {{ $t('billing.subscriptions.no_active') }}
                        </div>
                        
                        <!-- Estado com Assinatura -->
                        <div v-else class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-light text-gray-800 flex items-center gap-2">
                                    {{ $t('billing.subscriptions.active') }} 
                                    <span class="font-bold text-primary-600">{{ activeSubscription.name }}</span>
                                    <span v-if="activeSubscription.status === 'canceled' || activeSubscription.cancel_at_period_end" class="text-xs bg-red-100 text-red-800 px-2 py-0.5 rounded ml-2 font-medium">
                                        {{ $t('subscription.pending_cancellation') }}
                                    </span>
                                </h3>
                                <div class="mt-2" v-if="activeSubscription.ends_at">
                                    <div v-if="activeSubscription.cancel_at_period_end" class="inline-flex items-center gap-2 text-warning-800 bg-warning-50 border border-warning-200 px-4 py-2.5 rounded-md text-sm font-medium">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        {{ $t('billing.subscriptions.downgrade_scheduled', { date: new Date(activeSubscription.ends_at).toLocaleDateString() }) }}
                                    </div>
                                    <p v-else class="text-sm text-gray-500">
                                        {{ $t('billing.subscriptions.renews_at') }}
                                        {{ new Date(activeSubscription.ends_at).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                            <a :href="route('subscription.portal')" target="_blank" class="inline-flex items-center gap-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 px-4 py-2 transition">
                                {{ $t('billing.subscriptions.manage') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- HISTÓRICO DE PAGAMENTOS -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                    <div class="p-6 bg-white border-b border-gray-200">
                        
                        <div class="mb-6">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">{{ $t('billing.history.title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ $t('billing.history.description') }}</p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('billing.history.date') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('billing.history.amount') }}
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ $t('billing.history.status') }}
                                        </th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">{{ $t('billing.history.download') }}</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="invoice in invoices" :key="invoice.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ new Date(invoice.date).toLocaleDateString() }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ invoice.total_formatted }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800': invoice.status === 'paid',
                                                    'bg-danger-100 text-danger-800': invoice.status === 'uncollectible' || invoice.status === 'void',
                                                    'bg-yellow-100 text-yellow-800': invoice.status === 'open',
                                                }"
                                            >
                                                {{ $t('billing.history.status.' + invoice.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a :href="invoice.invoice_pdf" target="_blank" class="text-primary-600 hover:text-primary-900 flex items-center justify-end gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                {{ $t('billing.history.download') }}
                                            </a>
                                        </td>
                                    </tr>
                                    <tr v-if="invoices.length === 0">
                                        <td colspan="4" class="px-6 py-10 text-center text-gray-500 italic">
                                            {{ $t('billing.history.no_invoices') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
