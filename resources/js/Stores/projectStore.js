import { defineStore } from 'pinia';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const CACHE_KEY_PREFIX = 'project_cache_';

export const useProjectStore = defineStore('project', () => {
    const projects = ref({ data: [], links: [], meta: {} });
    const currentProject = ref(null);
    const isLoading = ref(false);
    const errors = ref({});
    const currentFilters = ref({});

    // Generate a cache key based on filters, sort, page
    function getCacheKey(page = 1, filters = {}, sortBy = 'created_at', sortDir = 'desc') {
        const filterString = Object.entries(filters)
                                 .filter(([, value]) => value)
                                 .map(([key, value]) => `${key}=${value}`)
                                 .join('&');
        return `${CACHE_KEY_PREFIX}page=${page}&sort=${sortBy}_${sortDir}&${filterString}`;
    }

    function setProjects(paginatedProjects, filters = {}, sortBy = 'created_at', sortDir = 'desc', page = 1) {
        projects.value = paginatedProjects;
        currentFilters.value = { ...filters, sortBy, sortDir, page };

        // Cache the data
        try {
            const cacheKey = getCacheKey(page, filters, sortBy, sortDir);
            localStorage.setItem(cacheKey, JSON.stringify(paginatedProjects));
            console.log('Cached data for key:', cacheKey);
        } catch (e) {
            console.error('Error saving to localStorage', e);
            clearOldestCache();
        }
    }

    // Load projects, trying cache first
    function invalidateCache() {
        console.log('Invalidating project cache...');
        Object.keys(localStorage).forEach(key => {
            if (key.startsWith(CACHE_KEY_PREFIX)) {
                localStorage.removeItem(key);
            }
        });
    }

    function clearOldestCache() {
        let oldestKey = null;
        let oldestTime = Infinity;
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key.startsWith(CACHE_KEY_PREFIX)) {
                localStorage.removeItem(key);
                console.warn('Cleared one cache item due to potential storage limit:', key);
                return;
            }
        }
    }

    function updateProjectInList(updatedProject) {
        if (projects.value && projects.value.data) {
            const index = projects.value.data.findIndex(p => p.id === updatedProject.id);
            if (index !== -1) {
                projects.value.data.splice(index, 1, updatedProject);
                const { page = 1, ...activeFilters } = currentFilters.value;
                const cacheKey = getCacheKey(page, activeFilters, currentFilters.value.sortBy, currentFilters.value.sortDir);
                localStorage.setItem(cacheKey, JSON.stringify(projects.value));
            }
        }
        invalidateCache();
    }

    function addProjectToList(newProject) {
        console.log('New project added (real-time):', newProject);
        invalidateCache();
    }

    function removeProjectFromList(projectId) {
        if (projects.value && projects.value.data) {
            projects.value.data = projects.value.data.filter(p => p.id !== projectId);
             const { page = 1, ...activeFilters } = currentFilters.value;
             const cacheKey = getCacheKey(page, activeFilters, currentFilters.value.sortBy, currentFilters.value.sortDir);
             localStorage.setItem(cacheKey, JSON.stringify(projects.value));
        }
        invalidateCache();
    }

    function tryLoadFromCache(page = 1, filters = {}, sortBy = 'created_at', sortDir = 'desc') {
        const cacheKey = getCacheKey(page, filters, sortBy, sortDir);
        const cachedData = localStorage.getItem(cacheKey);
        if (cachedData) {
            console.log('Loaded from cache:', cacheKey);
            projects.value = JSON.parse(cachedData);
            currentFilters.value = { ...filters, sortBy, sortDir, page };
            return true;
        }
        return false;
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
        invalidateCache,
        tryLoadFromCache,
        currentFilters,
    };
});