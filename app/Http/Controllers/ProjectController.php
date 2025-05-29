<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ProjectController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $request->validate([
            'sort_by' => 'nullable|string|in:name,status,created_at',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:pending,in-progress,completed',
        ]);

        $filters = $request->only(['search', 'status']);
        $sortBy = $request->input('sort_by', 'created_at'); // Default sort
        $sortDirection = $request->input('sort_direction', 'desc');

        $projects = Project::with(['tasks']) // Eager load only tasks, subtasks if needed on detail page
            ->where('user_id', Auth::id())
            ->filter($filters) // Apply scopeFilter
            ->orderBy($sortBy, $sortDirection)
            ->paginate(10)
            ->withQueryString(); // Appends query parameters to pagination links

        return Inertia::render('Projects/Index', [
            'projects' => \App\Http\Resources\ProjectResource::collection($projects),
            'filters' => $filters, // Pass current filters back to frontend
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
            'projectStatuses' => ['pending', 'in-progress', 'completed'], // For filter dropdown
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Projects/CreateEditForm', [
            'project' => null,
            'projectStatuses' => ['pending', 'in-progress', 'completed'],
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Auth::user()->projects()->create($request->validated());
        event(new \App\Events\ProjectCreated($project));
        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function show(Project $project): Response
    {
        $this->authorize('view', $project);
        $project->load(['tasks.subtasks']);
        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);
        $project->load(['tasks.subtasks']);
        return Inertia::render('Projects/CreateEditForm', [
            'project' => $project,
            'projectStatuses' => ['pending', 'in-progress', 'completed'],
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $project->update($request->validated());
        event(new \App\Events\ProjectUpdated($project));
        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        $userId = $project->user_id;
        $projectId = $project->id;
        $project->delete();
        event(new \App\Events\ProjectDeleted($projectId, $userId));
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
