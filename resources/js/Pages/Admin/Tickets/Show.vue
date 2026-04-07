<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    ticket: Object
});

const replyForm = useForm({
    mensagem: ''
});

const statusForm = useForm({
    status: props.ticket.status
});

const enviarResposta = () => {
    replyForm.post(route('admin.tickets.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            statusForm.status = 'respondido'; // Sync interface with Laravel controller behavior
        }
    });
};

const atualizarStatus = () => {
    statusForm.put(route('admin.tickets.update', props.ticket.id), {
        preserveScroll: true,
    });
};

const formatData = (iso) => {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString() + ' ' + new Date(iso).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
};
</script>

<template>
    <Head :title="`Ticket #${ticket.id}`" />

    <AppLayout>
        <template #hero>
            <div class="bg-primary-50 border-b border-primary-100 py-10 dark:bg-gray-800 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Link :href="route('admin.tickets.index')" class="text-gray-400 hover:text-gray-600 transition shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </Link>
                        <h1 class="text-2xl sm:text-3xl font-light text-gray-700 dark:text-gray-200">
                            Ticket #{{ String(ticket.id).padStart(4, '0') }}
                        </h1>
                    </div>
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-1.5 rounded-lg shadow-sm">
                        <span class="text-xs uppercase font-bold text-gray-500 dark:text-gray-400 pl-2">Status do Chamado:</span>
                        <select v-model="statusForm.status" @change="atualizarStatus" class="border-transparent rounded text-sm focus:ring-primary-500 font-bold ml-1 cursor-pointer"
                            :class="{
                                'text-danger-700 bg-danger-50 dark:bg-danger-900/30': statusForm.status === 'aberto',
                                'text-warning-700 bg-warning-50 dark:bg-warning-900/30': statusForm.status === 'respondido',
                                'text-success-700 bg-success-50 dark:bg-success-900/30': statusForm.status === 'fechado',
                            }">
                            <option value="aberto" class="bg-white text-gray-900">🚨 Aberto</option>
                            <option value="respondido" class="bg-white text-gray-900">⏳ Respondido</option>
                            <option value="fechado" class="bg-white text-gray-900">✅ Fechado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Coluna Principal: Timeline (Chat-like) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Mensagem Inicial -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 relative ml-4">
                        <div class="absolute top-6 -left-4">
                            <div class="bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-300 rounded-full p-1.5 border-4 border-gray-50 dark:border-gray-900">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>
                        <div class="flex justify-between items-start mb-4 border-b border-gray-100 dark:border-gray-700 pb-3">
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white">{{ ticket.usuario?.name || 'Cliente Mestre' }}</h3>
                                <p class="font-normal text-xs text-gray-500">Ticket Original Emitido</p>
                            </div>
                            <span class="text-xs text-gray-400 font-mono bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded">{{ formatData(ticket.created_at) }}</span>
                        </div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-2">{{ ticket.assunto }}</h4>
                        <div class="text-gray-700 dark:text-gray-300 font-normal whitespace-pre-wrap leading-relaxed text-sm bg-gray-50/50 dark:bg-gray-900/50 p-4 border border-gray-100 dark:border-gray-700 rounded-md">{{ ticket.mensagem }}</div>
                        
                        <!-- Lista de Anexos -->
                        <div v-if="ticket.anexos && ticket.anexos.length > 0" class="mt-4 pt-4">
                            <h5 class="text-xs font-semibold uppercase text-gray-400 tracking-wider mb-2 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                Anexos enviados pelo cliente:
                            </h5>
                            <ul class="flex flex-wrap gap-2">
                                <li v-for="anexo in ticket.anexos" :key="anexo.id">
                                    <a :href="`/storage/${anexo.file_path}`" target="_blank" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 transition text-sm flex items-center text-primary-600 dark:text-primary-400 font-medium">
                                        {{ anexo.file_name }}
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Timeline de Respostas Dinâmicas -->
                    <div v-for="resposta in ticket.respostas" :key="resposta.id" 
                         class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border p-6 relative transition-all" 
                         :class="resposta.is_admin ? 'border-primary-300 dark:border-primary-700 bg-primary-50/10 ml-4' : 'border-gray-200 dark:border-gray-700 mr-4'">
                        
                        <div class="absolute top-6" :class="resposta.is_admin ? '-left-4' : '-right-4'">
                            <div class="rounded-full p-1.5 border-4 border-gray-50 dark:border-gray-900" :class="resposta.is_admin ? 'bg-primary-500 text-white' : 'bg-gray-200 text-gray-500 dark:bg-gray-600'">
                                <svg v-if="resposta.is_admin" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-start mb-3 border-b border-gray-100 dark:border-gray-700 pb-2">
                            <h3 class="font-bold flex flex-col" :class="resposta.is_admin ? 'text-primary-700 dark:text-primary-400' : 'text-gray-900 dark:text-white'">
                                {{ resposta.is_admin ? 'Suporte Técnico (Administrador)' : (resposta.usuario?.name || 'Cliente') }}
                                <span v-if="resposta.is_admin" class="text-[10px] uppercase font-bold text-primary-500 tracking-wider">Resposta Autorizada</span>
                            </h3>
                            <span class="text-xs text-gray-400 font-mono bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded border border-transparent" :class="{'border-primary-100 dark:border-primary-800 bg-white dark:bg-gray-800': resposta.is_admin}">{{ formatData(resposta.created_at) }}</span>
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 font-normal whitespace-pre-wrap text-sm leading-relaxed" :class="{'bg-white dark:bg-gray-800 p-2': resposta.is_admin}">{{ resposta.mensagem }}</div>
                    </div>

                    <!-- Formulário de Resposta Interativa (Injeção de is_admin) -->
                    <div v-if="ticket.status !== 'fechado'" class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 mt-8 shadow-inner ml-4 relative">
                        <div class="absolute top-6 -left-4">
                            <div class="bg-primary-100 text-primary-500 rounded-full p-1.5 border-4 border-gray-50 dark:border-gray-900 shadow">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            </div>
                        </div>
                        
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 ml-2 flex items-center">
                            Área de Resposta do Administrador
                        </h4>
                        <form @submit.prevent="enviarResposta">
                            <textarea v-model="replyForm.mensagem" rows="4" class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:text-white transition-all focus:h-32" placeholder="Escreva a instrução ou solução técnica. O cliente será notificado na área remota dele..." required></textarea>
                            <InputError :message="replyForm.errors.mensagem" class="mt-2" />
                            <div class="mt-4 flex justify-end">
                                <PrimaryButton :processing="replyForm.processing" type="submit" class="shadow-sm">Inserir Resposta Segura e Alterar Status para Em Espera</PrimaryButton>
                            </div>
                        </form>
                    </div>

                    <div v-else class="text-center p-6 bg-gray-100 dark:bg-gray-800/80 text-gray-500 dark:text-gray-400 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 text-sm font-medium">
                        Um administrador definiu este ticket como <span class="font-bold text-gray-700 dark:text-gray-200">Fechado</span>. Ele está imutável a menos que o status superior seja revertido.
                    </div>

                </div>

                <!-- Coluna Aside: Metadados Estruturais do Vínculo -->
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5 sticky top-6">
                        <h3 class="text-xs font-bold text-gray-900 dark:text-gray-100 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-2 mb-4">Métricas do Acionamento</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Autor Original <span class="ml-1 opacity-50">(ID: {{ ticket.user_id }})</span>
                                </p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ ticket.usuario?.name || 'Conta Deletada' }}</p>
                                <p class="text-xs text-primary-600 dark:text-primary-400 hover:underline cursor-pointer truncate">{{ ticket.usuario?.email || '' }}</p>
                            </div>
                            
                            <div v-if="ticket.projeto">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium flex gap-1 items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                    Dominio de Origem (Crawler)
                                </p>
                                <a :href="ticket.projeto.url" target="_blank" class="text-sm font-bold text-gray-800 dark:text-gray-200 hover:text-primary-600 transition flex items-center mt-1 border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 rounded-md group">
                                    <span class="truncate">{{ ticket.projeto.name }}</span>
                                    <svg class="w-3 h-3 ml-auto opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                            
                            <div v-else>
                                 <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium flex gap-1 items-center mt-4">Categoria / Domínio</p>
                                 <div class="text-sm font-bold text-gray-600 dark:text-gray-400 mt-1">Dúvida Sistêmica Global</div>
                            </div>

                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] text-gray-500 uppercase tracking-wide font-medium">Cronologia (Abertura Inicial)</p>
                                <p class="text-sm font-semibold font-mono text-gray-900 dark:text-white mt-1">{{ formatData(ticket.created_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </template>
    </AppLayout>
</template>
