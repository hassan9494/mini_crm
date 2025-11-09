# Mini CRM Backend – Architecture Diagram

```mermaid
graph TD
    subgraph Clients
        A[API Clients / Frontend]
    end

    A -->|HTTPS + Bearer Token| B[Laravel HTTP Kernel]
    B --> C[Route Middleware
(role, auth:sanctum)]
    C --> D[Domain Controllers]

    subgraph Domain Layer
        D --> E[Services]
        E --> F[Repositories / Eloquent Models]
        E --> G[Events]
        G --> H[Listeners]
        H --> I[Notifications]
    end

    F -->|Reads/Writes| J[(MySQL / PostgreSQL)]

    subgraph Async
        E --> K[Jobs]
        K --> L[Queue Workers]
        L --> G
    end

    E --> M[DashboardCache (Redis)]
    M --> D

    subgraph Scheduler
        N[Laravel Scheduler]
        N --> K
    end
```

**Flow Summary**

1. Requests enter through the HTTP kernel, passing Sanctum authentication and role middleware.
2. Controllers delegate business logic to domain services which orchestrate models, events, and cache operations.
3. Services dispatch domain events to listeners that update aggregates and trigger notifications.
4. Jobs run asynchronously via the queue workers; scheduled tasks enqueue jobs daily for status recalculations and follow-up reminders.
5. Dashboard metrics are cached in Redis and invalidated automatically after relevant domain changes.

**Recent Enhancements (v1.1)**

- **Sanctum Authentication**: Migrated from Passport to Laravel Sanctum for simpler token-based auth
- **Enhanced Status Logic**: Client status now considers both time-based and count-based rules (3+ comms in 7 days = Hot)
- **Top Sales Reps**: Dashboard now includes top 5 sales reps by active client count
- **Avg Communication Frequency**: Dashboard calculates average days between communications per client
- **Company Field**: Clients now have an optional company field for better organization
