<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextareaInput.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    project: Object,
    projectStatuses: Array,
    errors: Object
});

const form = useForm({
    _method: props.project ? 'PUT' : 'POST',
    name: props.project?.name || '',
    description: props.project?.description || '',
    status: props.project?.status || 'pending',
});

const pageTitle = props.project ? 'Edit Project' : 'Create Project';
const submitButtonText = props.project ? 'Update Project' : 'Create Project';

const submit = () => {
    const routeName = props.project ? 'projects.update' : 'projects.store';
    const routeParams = props.project ? [props.project.id] : [];

    form.post(route(routeName, ...routeParams), {
        onError: (formErrors) => {
            console.log("Form submission error:", formErrors);
        },
        onSuccess: () => {
            form.reset();
        }
    });
};
</script>

<template>
    <AppLayout :title="pageTitle">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ pageTitle }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Project Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="description" value="Description" />
                            <TextareaInput
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full"
                                rows="4"
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="status" value="Status" />
                            <SelectInput
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full"
                                :options="projectStatuses.map(s => ({ value: s, label: s.charAt(0).toUpperCase() + s.slice(1).replace('-', ' ') }))"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <Link :href="route('projects.index')" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-4">
                                Cancel
                            </Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                {{ submitButtonText }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>