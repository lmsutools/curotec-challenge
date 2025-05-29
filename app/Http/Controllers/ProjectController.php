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

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        // Eager load tasks and their subtasks
        $projects = Project::with(['tasks.subtasks'])
            ->where('user_id', Auth::id()) // Only user's projects
            ->latest() // Default sort by newest
            ->paginate(10); // Basic pagination

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
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
