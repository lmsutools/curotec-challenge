<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { useProjectStore } from '@/Stores/projectStore';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    projects: Object,
});

const projectStore = useProjectStore();
const page = usePage();

projectStore.setProjects(props.projects);

// Watch for changes in props.projects (e.g., after pagination)
watch(() => props.projects, (newProjects) => {
    projectStore.setProjects(newProjects);
}, { deep: true });


const deleteProject = (projectId) => {
    if (confirm('Are you sure you want to delete this project?')) {
        router.delete(route('projects.destroy', projectId), {
            preserveScroll: true,
            onSuccess: () => {
            }
        });
    }
};

onMounted(() => {
    if (page.props.auth.user) {
        console.log('Listening for project events...');
        window.Echo.private(`users.${page.props.auth.user.id}`)
            .listen('.ProjectCreated', (event) => {
                console.log('ProjectCreated event received:', event.project);
                alert(`New project created: ${event.project.name}`);
                // projectStore.addProjectToList(event.project);
                router.reload({ only: ['projects'], preserveScroll: true }); // Reload data
            })
            .listen('.ProjectUpdated', (event) => {
                console.log('ProjectUpdated event received:', event.project);
                projectStore.updateProjectInList(event.project);
            })
            .listen('.ProjectDeleted', (event) => {
                console.log('ProjectDeleted event received:', event.projectId);
                projectStore.removeProjectFromList(event.projectId);
            });
    }
});

onUnmounted(() => {
    if (page.props.auth.user) {
        window.Echo.leave(`users.${page.props.auth.user.id}`);
        // window.Echo.leave(`projects`);
    }
});

</script>

<template>
    <AppLayout title="Projects">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Projects
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                        {{ $page.props.flash.success }}
                    </div>
                    <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        {{ $page.props.flash.error }}
                    </div>

                    <Link :href="route('projects.create')" class="mb-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Create New Project
                    </Link>

                    <div v-if="projectStore.projects && projectStore.projects.data && projectStore.projects.data.length > 0">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tasks</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="project in projectStore.projects.data" :key="project.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ project.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ project.status }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ project.tasks?.length || 0 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('projects.edit', project.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                        <button @click="deleteProject(project.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <Pagination class="mt-6" :links="projectStore.projects.links" />
                    </div>
                    <div v-else>
                        <p>No projects found. <Link :href="route('projects.create')" class="text-blue-500 hover:text-blue-700">Create one!</Link></p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>