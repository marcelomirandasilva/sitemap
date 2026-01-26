<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CrawlerStatus from '@/Components/Crawler/CrawlerStatus.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    projects: {
        type: Array,
        default: () => []
    }
});

// Formulário para adicionar o site
const form = useForm({
    url: '',
});

const submitProject = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
             form.reset();
             showAddModal.value = false;
        },
    });
};

import { ref, computed } from 'vue';
import ProjectCard from '@/Components/Project/ProjectCard.vue';

const showAddModal = ref(false);
const searchQuery = ref('');

const filteredProjects = computed(() => {
    if (!searchQuery.value) return props.projects;
    const query = searchQuery.value.toLowerCase();
    return props.projects.filter(p => 
        p.url.toLowerCase().includes(query) || 
        (p.name && p.name.toLowerCase().includes(query))
    );
});
</script>

<template>
    <Head title="Control Panel" />

    <AppLayout>
        <template #hero>
            <!-- Hero "Pro" -->
            <div class="bg-blue-50 border-b border-blue-100 py-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-3xl font-light text-gray-700 flex items-center justify-center gap-3">
                        <svg class="w-8 h-8 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        Control Panel
                    </h1>
                </div>
            </div>

            <!-- Toolbar & Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                
                <!-- Action Bar -->
                <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-8 gap-4">
                    <button 
                        @click="showAddModal = !showAddModal"
                        class="bg-[#007da0] hover:bg-[#006480] text-white font-bold py-2.5 px-6 rounded text-sm uppercase tracking-wide transition shadow-sm flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Another Website
                    </button>

                    <div class="relative w-full md:w-96">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Quick search for a website..." 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded text-gray-600 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 bg-gray-50 shadow-inner"
                        >
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </div>

                <!-- Add Form Toggle (Simple) -->
                <div v-if="showAddModal" class="mb-8 p-6 bg-gray-50 border border-blue-100 rounded-lg animate-fade-in-down">
                    <h3 class="text-lg font-light text-gray-600 mb-4 text-center">Add New Website</h3>
                    <form @submit.prevent="submitProject" class="max-w-md mx-auto flex gap-2">
                        <input 
                            v-model="form.url"
                            type="url" 
                            required
                            placeholder="https://example.com"
                            class="flex-1 border border-gray-300 shadow-inner px-4 py-3 text-gray-600 focus:ring-1 focus:ring-blue-400 focus:border-blue-400 rounded-md"
                        >
                        <button 
                            :disabled="form.processing"
                            type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 rounded shadow-sm disabled:opacity-50"
                        >
                            ADD
                        </button>
                    </form>
                </div>

                <!-- Project Grid -->
                <div v-if="filteredProjects.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <ProjectCard 
                        v-for="project in filteredProjects" 
                        :key="project.id" 
                        :project="project" 
                    />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-20 text-gray-500">
                    <p class="text-xl font-light">No websites found.</p>
                </div>

                <!-- Filters / Footer Info -->
                <div class="mt-8 flex justify-between items-center text-xs text-gray-400 uppercase tracking-widest">
                    <div>Show All [{{ projects.length }}]</div>
                    <button class="hover:text-gray-600 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        Switch to List View
                    </button>
                </div>

            </div>
        </template>
    </AppLayout>
</template>