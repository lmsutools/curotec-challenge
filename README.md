# Laravel 10 & Vue 3 Full Stack Integration Challenge

This project is a data management system for "Projects," demonstrating integration between a Laravel backend and a Vue 3 frontend using Inertia.js. It includes CRUD operations, real-time updates via Laravel Echo and Pusher, advanced filtering, sorting, pagination, and client-side caching with Pinia.

## Tech Stack

*   **Primary:** Laravel 10
*   **Secondary:** Vue.js 3 (Composition API)
*   **Additional:**
    *   Inertia.js 1.0
    *   PostgreSQL 15
    *   Pinia 2 (State Management)
    *   Pest 2 (PHP Testing)
    *   Laravel Echo & Pusher (Real-time)
    *   Tailwind CSS (via Jetstream)

## Features

*   **Project Management:** CRUD operations for projects.
*   **Task & Subtask Relationships:** Projects can have tasks, and tasks can have subtasks (demonstrating Eloquent relationships).
*   **User Authentication:** Provided by Laravel Jetstream. Projects are user-specific.
*   **Real-time Updates:** Changes to projects (create, update, delete) are broadcast using Laravel Events and Pusher, updating the UI in real-time for connected clients viewing the same user's data.
*   **Advanced Filtering:** Filter projects by search term (name, description) and status.
*   **Sorting:** Sort projects by name, status, or creation date.
*   **Pagination:** Project list is paginated.
*   **Client-Side Caching:** Basic localStorage caching of fetched project lists via Pinia to improve perceived performance on re-visits (though Inertia's server-driven nature makes this supplemental).
*   **Responsive UI:** Basic responsiveness via Tailwind CSS.

## Setup Instructions

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/lmsutools/curotec-challenge
    cd inertia-challenge
    ```

2.  **Install PHP Dependencies:**
    ```bash
    composer install
    ```

3.  **Install NPM Dependencies:**
    ```bash
    npm install
    ```

4.  **Environment Setup:**
    *   Copy `.env.example` to `.env`:
        ```bash
        cp .env.example .env
        ```
    *   Generate an application key:
        ```bash
        php artisan key:generate
        ```
    *   Configure your database connection in `.env` (ensure you have PostgreSQL running and a database created):
        ```env
        DB_CONNECTION=pgsql
        DB_HOST=127.0.0.1
        DB_PORT=5432
        DB_DATABASE=inertia_challenge_db
        DB_USERNAME=your_postgres_user
        DB_PASSWORD=your_postgres_password
        ```
    *   Configure Pusher credentials in `.env` (sign up at [pusher.com](https://pusher.com) for a free sandbox plan):
        ```env
        BROADCAST_DRIVER=pusher
        PUSHER_APP_ID=your_app_id
        PUSHER_APP_KEY=your_app_key
        PUSHER_APP_SECRET=your_app_secret
        PUSHER_APP_CLUSTER=your_cluster

        VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
        VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
        # Add other VITE_PUSHER vars if needed as per bootstrap.js
        ```

5.  **Run Database Migrations:**
    ```bash
    php artisan migrate
    ```
    *(Optional: Run seeders if you create any: `php artisan db:seed`)*

6.  **Compile Frontend Assets:**
    *   For development (with hot reloading):
        ```bash
        npm run dev
        ```
    *   For production:
        ```bash
        npm run build
        ```

7.  **Serve the Application:**
    ```bash
    php artisan serve
    ```
    The application should be available at `http://127.0.0.1:8000`.

## Application Structure & Design Patterns

*   **Backend (Laravel):**
    *   **MVC Pattern:** Standard Laravel structure.
    *   **Eloquent ORM:** Used for database interaction and relationships (`Project`, `Task`, `Subtask` models).
    *   **Resource Controllers:** `ProjectController` handles CRUD for projects.
    *   **Form Requests:** `StoreProjectRequest` and `UpdateProjectRequest` for validation.
    *   **Policies:** `ProjectPolicy` for authorization.
    *   **Events & Broadcasting:** Laravel Events (`ProjectCreated`, `Updated`, `Deleted`) are broadcast via Pusher for real-time updates. Uses `ShouldBroadcastNow`.
    *   **Query Scopes:** `scopeFilter` in `Project` model for cleaner filtering logic.
    *   **Dependency Injection:** Used throughout Laravel (e.g., in controllers, event listeners).
    *   **Service Providers:** Standard Laravel providers, including `BroadcastServiceProvider` for broadcasting.
*   **Frontend (Vue 3 & Inertia):**
    *   **Inertia.js:** Connects Laravel backend to Vue frontend, allowing server-side routing with client-side rendering (SPA-like experience).
    *   **Vue 3 Composition API:** Used in all Vue components for better organization and reusability.
    *   **Pinia:** For global state management (`projectStore`). Stores project list, manages loading/error states, and handles some real-time update logic.
    *   **Reusable Components:** Standard Jetstream components (`InputLabel`, `TextInput`, etc.) and custom components (`Pagination`, `TextareaInput`, `SelectInput`).
    *   **Pages:** Inertia pages in `resources/js/Pages/Projects` (`Index.vue`, `CreateEditForm.vue`).
    *   **Layouts:** `AppLayout.vue` (from Jetstream) provides the main application shell.
*   **Data Flow (Inertia):**
    1.  User action (e.g., click link, submit form).
    2.  Inertia makes an XHR request to Laravel.
    3.  Laravel controller processes request, fetches data.
    4.  Controller returns an `Inertia::render()` response with page component name and props.
    5.  Inertia dynamically updates the Vue page component with new props.
*   **Real-time Updates:**
    1.  Action in Laravel (e.g., `ProjectController@store`) dispatches an event.
    2.  Event (implementing `ShouldBroadcastNow`) is sent to Pusher.
    3.  Laravel Echo (in `Projects/Index.vue`) listens for the event on a private channel.
    4.  On receiving the event, Pinia store is updated, or data is reloaded, causing UI to refresh.

## API Endpoints & Payloads (Simplified for Inertia context)

Inertia doesn't expose traditional REST APIs for direct consumption by other clients, but the controller actions and their expected data are as follows:

*   **`GET /projects`**:
    *   Params: `page`, `search`, `status`, `sort_by`, `sort_direction`
    *   Returns: Inertia response for `Projects/Index` page with paginated projects, filters, etc.
*   **`GET /projects/create`**:
    *   Returns: Inertia response for `Projects/CreateEditForm` page.
*   **`POST /projects`**:
    *   Payload: `{ name: string, description?: string, status: string }`
    *   Returns: Redirect to `/projects` with success/error flash message.
*   **`GET /projects/{project}/edit`**:
    *   Returns: Inertia response for `Projects/CreateEditForm` page with project data.
*   **`PUT/PATCH /projects/{project}`**:
    *   Payload: `{ name?: string, description?: string, status?: string }`
    *   Returns: Redirect to `/projects` with success/error flash message.
*   **`DELETE /projects/{project}`**:
    *   Returns: Redirect to `/projects` with success/error flash message.

## Code Comments & Complex Logic

*   Inline comments are provided for complex logic sections, especially in Vue components related to filtering, sorting, real-time event handling, and Pinia store interactions.
*   Backend controller methods and event classes include comments explaining their purpose and interactions.

## Running Tests

```bash
php artisan test tests/Feature/ProjectManagementTest.php
```