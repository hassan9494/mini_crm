<h1 align="center">Mini CRM Backend</h1>

Production-ready Laravel 12 backend for a lightweight CRM with Laravel Sanctum token authentication, Spatie role-based access control, event-driven updates, queued notifications, scheduled automation, and cached dashboard analytics.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Features](#features)
3. [Business Logic Automation](#business-logic-automation)
4. [Getting Started](#getting-started)
5. [Environment Configuration](#environment-configuration)
6. [Authentication & Authorization](#authentication--authorization)
7. [Background Processing & Scheduling](#background-processing--scheduling)
8. [API Endpoints Overview](#api-endpoints-overview)
9. [Testing](#testing)
10. [API Documentation](#api-documentation)
11. [Diagrams](#diagrams)

## Architecture Overview

- **Framework:** Laravel 12 (Application builder + bootstrap configuration)
- **Domain Structure:** `app/Domains/*` encapsulates controllers, services, models, policies, events, listeners, jobs, and notifications grouped by bounded context.
- **Authentication:** Laravel Sanctum issuing personal access tokens with role-based abilities.
- **Authorization:** Spatie Laravel Permission with Admin, Manager, and Sales Rep roles.
- **Caching:** Redis-backed cache (via `predis/predis`) for dashboard metrics with tag-based invalidation.
- **Queues:** Default queue driver configurable (recommended: Redis). All notifications and scheduled jobs are queued.
- **Events & Jobs:** Communication and follow-up workflows trigger listeners, notifications, and scheduled jobs for automated reminders and status updates.

## Features

- OAuth2 login, logout, and profile endpoints returning unified JSON responses.
- Client management with granular policies and role-specific access.
- Logging communications per client, automatically updating last-contact timestamps.
- Follow-up tasks with events, queued notifications, and due-date automation.
- Cached dashboard metrics (clients by status, follow-up stats, recent activity) with cache invalidation on domain changes.
- Daily scheduled jobs for client status recalculation and follow-up reminders.
- Comprehensive feature and unit test coverage for core flows.

## Business Logic Automation

- **Client status lifecycle**
  - **Hot:** 3+ communications recorded within the last 7 days via `UpdateClientStatusesJob`.
  - **Inactive:** No communications for 30+ days. The same job downgrades status nightly.
  - Cache tags are flushed automatically so dashboard metrics reflect new statuses.
- **Follow-up orchestration**
  - Sales reps and managers schedule follow-ups with due dates and notes.
  - `CheckFollowUpsDueTodayJob` emits `FollowUpDue` events for pending tasks due today or overdue.
  - `SendFollowUpNotificationListener` queues email/database notifications for the assigned owner.
- **Communication sync**
  - Recording a communication updates the client’s `last_contacted_at` timestamp through the `UpdateClientLastContactListener`.
  - Communication volume feeds the client status automation and dashboard metrics.

## Getting Started

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or PostgreSQL 13+
- Node.js & npm (optional, for frontend assets)

### Step-by-Step Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd mini_crm
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Copy environment file and generate application key**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database connection**
   
   Open `.env` and update the database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=mini_crm
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```
   
   **Create the database:**
   ```bash
   # For MySQL
   mysql -u root -p -e "CREATE DATABASE mini_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # For PostgreSQL
   createdb mini_crm
   ```

5. **Run migrations and seed the database**
   ```bash
   php artisan migrate --seed
   ```
   
   This will create:
   - All required tables
   - Three test users with roles:
     - **Admin:** admin@crm.com / password
     - **Manager:** manager@crm.com / password
     - **Sales Rep:** sales@crm.com / password

6. **Verify Sanctum is ready**
   
   Sanctum is already configured and ready to use. No additional setup required!
   
   The `personal_access_tokens` table was created during migration.

7. **Start the development server**
   ```bash
   php artisan serve
   ```
   
   The API will be available at `http://127.0.0.1:8000`

8. **Start background workers** (in separate terminals)
   ```bash
   # Terminal 2: Queue worker for processing jobs and notifications
   php artisan queue:work
   
   # Terminal 3: Scheduler for automated tasks
   php artisan schedule:work
   ```

### Quick Test - Authentication Flow

1. **Login to get access token:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
     -H "Content-Type: application/json" \
     -H "Accept: application/json" \
     -d '{
       "email": "admin@crm.com",
       "password": "password"
     }'
   ```
   
   **Response:**
   ```json
   {
     "success": true,
     "data": {
       "token_type": "Bearer",
       "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
       "expires_in": 31536000,
       "scopes": ["*"],
       "user": {
         "id": 1,
         "name": "System Admin",
         "email": "admin@crm.com",
         "role": "admin",
         "roles": [{"name": "admin"}]
       }
     }
   }
   ```

2. **Use the token for authenticated requests:**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/v1/auth/me \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
     -H "Accept: application/json"
   ```

3. **Access dashboard (requires manager/admin role):**
   ```bash
   curl -X GET http://127.0.0.1:8000/api/v1/dashboard \
     -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
     -H "Accept: application/json"
   ```

### Troubleshooting

**Issue: "SQLSTATE[HY000] [1045] Access denied"**
- Verify database credentials in `.env`
- Ensure MySQL/PostgreSQL service is running
- Check user has proper permissions

**Issue: "401 Unauthorized" with valid token**
- Ensure the `personal_access_tokens` table exists in your database
- Verify the token is being sent in the `Authorization: Bearer {token}` header
- Clear cache: `php artisan config:clear && php artisan cache:clear`
- Check that the user still exists and the token hasn't been revoked

**Issue: "403 Forbidden" on API requests**
- Verify you're using the correct role (admin/manager/sales_rep)
- Check token scopes match endpoint requirements
- Ensure `Authorization: Bearer {token}` header is present

**Issue: Jobs not processing**
- Ensure queue worker is running: `php artisan queue:work`
- Check `QUEUE_CONNECTION` in `.env` (default: `database`)
- View failed jobs: `php artisan queue:failed`

## Environment Configuration

Key settings in `.env`:

| Variable | Description |
| --- | --- |
| `APP_URL` | Base URL for API responses. |
| `DB_*` | Database connection (MySQL default). Update for PostgreSQL if preferred. |
| `CACHE_DRIVER` | Recommended `redis` for dashboard cache invalidation. |
| `QUEUE_CONNECTION` | Queue driver (e.g. `redis`). |
| `BROADCAST_DRIVER`, `MAIL_*` | Configure email channel for follow-up notifications. |
| `SANCTUM_STATEFUL_DOMAINS` | Comma-separated list of domains for SPA authentication (optional). |

Optional helpers are provided in `composer.json` (`composer dev`) for running server, queue, and Vite concurrently during local development.

## Authentication & Authorization

- **Sanctum Token Abilities**
  - `clients:read` - View client records
  - `clients:write` - Create/update clients
  - `followups:manage` - Manage follow-ups
  - `dashboard:view` - View dashboard analytics
  - `*` - Admin wildcard (all abilities)

- **Roles:**
  - **Admin:** full access to all endpoints.
  - **Manager:** manages team clients and follow-ups; access to dashboard metrics.
  - **Sales Rep:** manages assigned clients' communications/follow-ups.

Use `/api/v1/auth/login` with seeded credentials (see `database/seeders/DatabaseSeeder.php`) to obtain a token. Include `Authorization: Bearer {token}` and required scopes for subsequent requests.

## Background Processing & Scheduling

- `CheckFollowUpsDueTodayJob` queued daily at 08:00 to emit `FollowUpDue` events.
- `UpdateClientStatusesJob` queued daily at 01:00 to adjust client status based on recency of communication.
- Queue workers must be running for notifications and jobs to execute.
- `bootstrap/app.php` registers middleware alias for `role` ensuring role-based access control on routes.

## API Endpoints Overview

All endpoints return JSON responses with this structure:
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

### Authentication Endpoints

#### POST /api/v1/auth/login
Login and receive access token.

**Request:**
```json
{
  "email": "admin@crm.com",
  "password": "password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1Q...",
    "expires_in": 31536000,
    "scopes": ["*"],
    "user": {
      "id": 1,
      "name": "System Admin",
      "email": "admin@crm.com",
      "role": "admin"
    }
  }
}
```

#### POST /api/v1/auth/logout
Revoke current access token.

**Headers:** `Authorization: Bearer {token}`

#### GET /api/v1/auth/me
Get authenticated user profile.

**Headers:** `Authorization: Bearer {token}`

---

### Client Management

#### GET /api/v1/clients
List clients (filtered by role).

**Scopes:** `clients:read`  
**Roles:** Admin (all), Manager (team), Sales Rep (assigned only)

**Query Parameters:**
- `status`: Filter by status (hot, warm, inactive)
- `assigned_to`: Filter by assigned user ID

#### POST /api/v1/clients
Create a new client.

**Scopes:** `clients:write`  
**Roles:** Admin, Manager

**Request:**
```json
{
  "name": "Acme Corp",
  "email": "contact@acme.com",
  "phone": "+1234567890",
  "status": "warm",
  "assigned_to": 3
}
```

#### PUT /api/v1/clients/{id}
Update client details.

**Scopes:** `clients:write`  
**Authorization:** Policy-based (own clients or team clients)

#### DELETE /api/v1/clients/{id}
Delete a client.

**Roles:** Admin, Manager only

---

### Communications

#### GET /api/v1/clients/{client}/communications
List all communications for a client.

#### POST /api/v1/clients/{client}/communications
Log a new communication.

**Request:**
```json
{
  "type": "call",
  "date": "2025-01-15 14:30:00",
  "notes": "Discussed Q1 requirements"
}
```

**Valid types:** `call`, `email`, `meeting`

---

### Follow-ups

#### GET /api/v1/clients/{client}/follow-ups
List follow-ups for a client.

#### POST /api/v1/clients/{client}/follow-ups
Schedule a new follow-up.

**Scopes:** `followups:manage`

**Request:**
```json
{
  "due_date": "2025-01-20 10:00:00",
  "notes": "Follow up on proposal"
}
```

#### PUT /api/v1/clients/{client}/follow-ups/{id}
Update follow-up status or details.

**Request:**
```json
{
  "status": "completed",
  "notes": "Completed successfully"
}
```

**Valid statuses:** `pending`, `completed`, `cancelled`

---

### Dashboard

#### GET /api/v1/dashboard
Get dashboard metrics and analytics.

**Scopes:** `dashboard:view`  
**Roles:** Admin, Manager

**Response:**
```json
{
  "success": true,
  "data": {
    "totals": {
      "clients": 45,
      "follow_ups_pending": 12,
      "follow_ups_overdue": 3,
      "follow_ups_due_soon": 5,
      "communications_last_7_days": 28
    },
    "clients_by_status": {
      "hot": 15,
      "warm": 20,
      "inactive": 10
    },
    "generated_at": "2025-01-15T10:30:00Z"
  }
}
```

---

### Role & Scope Matrix

| Endpoint | Admin | Manager | Sales Rep | Required Scopes |
|----------|-------|---------|-----------|-----------------|
| Login/Logout/Me | ✅ | ✅ | ✅ | - |
| Dashboard | ✅ | ✅ | ❌ | `dashboard:view` |
| List Clients | ✅ (all) | ✅ (team) | ✅ (assigned) | `clients:read` |
| Create Client | ✅ | ✅ | ❌ | `clients:write` |
| Update Client | ✅ | ✅ (team) | ✅ (assigned) | `clients:write` |
| Delete Client | ✅ | ✅ | ❌ | `clients:write` |
| Communications | ✅ | ✅ | ✅ | `clients:read` |
| Follow-ups | ✅ | ✅ | ✅ | `followups:manage` |

## Testing

Run the entire suite:

```bash
php artisan test
```

Coverage highlights:

- **Feature tests** verify dashboard metrics, follow-up permissions, and cache invalidation.
- **Unit tests** confirm scheduled jobs dispatch events and listeners send notifications.
- Factories and database seeding ensure isolated, repeatable scenarios.

## API Documentation

An OpenAPI 3.1 specification is available at `docs/openapi.yaml`. Import the file into tools such as [Stoplight](https://stoplight.io), [Insomnia](https://insomnia.rest), or [Swagger UI](https://swagger.io/tools/swagger-ui/) to explore endpoints and schemas.

For a quick start, a Postman collection is available at `docs/postman-mini-crm.json` (see [API Endpoints Overview](#api-endpoints-overview) for route summaries).

## Diagrams

- **System Architecture:** See `docs/architecture.md` for a high-level Mermaid diagram covering HTTP requests, jobs, events, and cache flows.
- **Entity Relationships (optional):** Extend the diagram file to add ERD views for clients, communications, follow-ups, and users as needed.

---

For additional details on implementation decisions, review domain-specific directories under `app/Domains` and associated tests in `tests/`.
