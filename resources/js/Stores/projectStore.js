import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useProjectStore = defineStore('project', () => {
    const projects = ref([]);
    const currentProject = ref(null);
    const isLoading = ref(false);
    const errors = ref({});

    function setProjects(paginatedProjects) {
        projects.value = paginatedProjects;
    }

    function updateProjectInList(updatedProject) {
        if (projects.value && projects.value.data) {
            const index = projects.value.data.findIndex(p => p.id === updatedProject.id);
            if (index !== -1) {
                projects.value.data.splice(index, 1, updatedProject);
            }
        }
    }

    function addProjectToList(newProject) {
        console.log('New project added (real-time):', newProject);
        // if (projects.value && projects.value.data) {
        //    projects.value.data.unshift(newProject);
        // }
    }

    function removeProjectFromList(projectId) {
        if (projects.value && projects.value.data) {
            projects.value.data = projects.value.data.filter(p => p.id !== projectId);
        }
    }


    return {
        projects,
        currentProject,
        isLoading,
        errors,
        setProjects,
        updateProjectInList,
        addProjectToList,
        removeProjectFromList,
    };
});