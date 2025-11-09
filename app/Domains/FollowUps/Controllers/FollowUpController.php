<?php

namespace App\Domains\FollowUps\Controllers;

use App\Domains\Clients\Models\Client;
use App\Domains\FollowUps\Models\FollowUp;
use App\Domains\FollowUps\Requests\FollowUpStoreRequest;
use App\Domains\FollowUps\Requests\FollowUpUpdateRequest;
use App\Domains\FollowUps\Resources\FollowUpResource;
use App\Domains\FollowUps\Services\FollowUpService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FollowUpController
{
    use ApiResponse;

    public function __construct(private readonly FollowUpService $service)
    {
    }

    public function index(Request $request, Client $client): JsonResponse
    {
        $filters = $request->only(['status', 'due_from', 'due_to']);
        $paginator = $this->service->paginateForClient($request->user(), $client, $filters);

        $items = array_map(
            fn (FollowUp $followUp) => (new FollowUpResource($followUp))->toArray($request),
            $paginator->items()
        );

        return $this->paginated($paginator->setCollection(collect($items)), 'Follow-ups retrieved');
    }

    public function store(FollowUpStoreRequest $request, Client $client): JsonResponse
    {
        $followUp = $this->service->create($request->user(), $client, $request->validated());

        return $this->success(new FollowUpResource($followUp), 'Follow-up created', 201);
    }

    public function show(Request $request, Client $client, FollowUp $followUp): JsonResponse
    {
        Gate::authorize('view', [$followUp, $client]);

        return $this->success(new FollowUpResource($followUp->load('owner')));
    }

    public function update(FollowUpUpdateRequest $request, Client $client, FollowUp $followUp): JsonResponse
    {
        $followUp = $this->service->update($request->user(), $client, $followUp, $request->validated());

        return $this->success(new FollowUpResource($followUp), 'Follow-up updated');
    }

    public function destroy(Request $request, Client $client, FollowUp $followUp): JsonResponse
    {
        $this->service->delete($request->user(), $client, $followUp);

        return $this->success(null, 'Follow-up deleted', 204);
    }
}
