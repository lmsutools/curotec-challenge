<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Support\Facades\Event;
use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use App\Events\ProjectDeleted;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;


class ProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        Event::fake();
    }

    public function test_project_index_page_is_rendered_and_shows_projects(): void
    {
        Project::factory()->count(3)->for($this->user)->create();
        Project::factory()->count(2)->create();

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/Index')
                ->has('projects.data', 3)
                ->where('projects.data.0.user_id', $this->user->id)
            );
    }

    public function test_project_create_page_is_rendered(): void
    {
        $this->get(route('projects.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/CreateEditForm')
                ->has('project', null)
                ->has('projectStatuses')
            );
    }

    public function test_authenticated_user_can_create_a_project(): void
    {
        $projectData = [
            'name' => 'My New Awesome Project',
            'description' => 'This is a detailed description.',
            'status' => 'in-progress',
        ];

        $response = $this->post(route('projects.store'), $projectData);

        $response->assertRedirect(route('projects.index'))
                 ->assertSessionHas('success', 'Project created successfully.');

        $this->assertDatabaseHas('projects', [
            'name' => 'My New Awesome Project',
            'description' => 'This is a detailed description.',
            'status' => 'in-progress',
            'user_id' => $this->user->id,
        ]);

        Event::assertDispatched(ProjectCreated::class, function ($event) use ($projectData) {
            return $event->project->name === $projectData['name'] &&
                   $event->project->user_id === $this->user->id;
        });
    }

    public function test_project_creation_fails_with_invalid_data(): void
    {
        $response = $this->post(route('projects.store'), [
            'name' => '',
            'status' => 'invalid_status',
        ]);

        $response->assertSessionHasErrors(['name', 'status']);
        $this->assertDatabaseMissing('projects', ['status' => 'invalid_status']);
        Event::assertNotDispatched(ProjectCreated::class);
    }


    public function test_project_edit_page_is_rendered_for_owned_project(): void
    {
        $project = Project::factory()->for($this->user)->create();

        $this->get(route('projects.edit', $project))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Projects/CreateEditForm')
                ->where('project.id', $project->id)
                ->where('project.name', $project->name)
                ->has('projectStatuses')
            );
    }

    public function test_authenticated_user_can_update_their_own_project(): void
    {
        $project = Project::factory()->for($this->user)->create(['status' => 'pending']);

        $updatedData = [
            'name' => 'Updated Project Title',
            'description' => 'Updated description here.',
            'status' => 'completed',
        ];

        $response = $this->put(route('projects.update', $project), $updatedData);

        $response->assertRedirect(route('projects.index'))
                 ->assertSessionHas('success', 'Project updated successfully.');

        $this->assertDatabaseHas('projects', array_merge(['id' => $project->id], $updatedData));

        Event::assertDispatched(ProjectUpdated::class, function ($event) use ($project) {
            return $event->project->id === $project->id &&
                   $event->project->status === 'completed';
        });
    }

    public function test_authenticated_user_cannot_update_another_users_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($otherUser)->create();

        $updatedData = [
            'name' => 'Malicious Update Attempt',
            'status' => 'pending',
        ];

        $this->put(route('projects.update', $project), $updatedData)
             ->assertForbidden();

        $this->assertDatabaseMissing('projects', ['name' => 'Malicious Update Attempt']);
        Event::assertNotDispatched(ProjectUpdated::class);
    }


    public function test_authenticated_user_can_delete_their_own_project(): void
    {
        $project = Project::factory()->for($this->user)->create();
        $projectId = $project->id;
        $userId = $this->user->id;

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertRedirect(route('projects.index'))
                 ->assertSessionHas('success', 'Project deleted successfully.');

        $this->assertModelMissing($project);

        Event::assertDispatched(ProjectDeleted::class, function ($event) use ($projectId, $userId) {
            return $event->projectId === $projectId &&
                   $event->userId === $userId;
        });
    }

    public function test_authenticated_user_cannot_delete_another_users_project(): void
    {
        $otherUser = User::factory()->create();
        $project = Project::factory()->for($otherUser)->create();

        $this->delete(route('projects.destroy', $project))
             ->assertForbidden();

        $this->assertModelExists($project);
        Event::assertNotDispatched(ProjectDeleted::class);
    }

    public function test_guest_cannot_access_project_management_routes(): void
    {
        Auth::logout();

        $project = Project::factory()->create();

        $this->get(route('projects.index'))->assertRedirect(route('login'));
        $this->get(route('projects.create'))->assertRedirect(route('login'));
        $this->post(route('projects.store'), ['name' => 'Test', 'status' => 'pending'])->assertRedirect(route('login'));
        $this->get(route('projects.edit', $project))->assertRedirect(route('login'));
        $this->put(route('projects.update', $project), ['name' => 'Test'])->assertRedirect(route('login'));
        $this->delete(route('projects.destroy', $project))->assertRedirect(route('login'));
    }
}