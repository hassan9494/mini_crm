<?php

namespace App\Domains\Communications\Controllers;

use App\Domains\Clients\Models\Client;
use App\Domains\Communications\Models\Communication;
use App\Domains\Communications\Requests\CommunicationStoreRequest;
use App\Domains\Communications\Requests\CommunicationUpdateRequest;
use App\Domains\Communications\Resources\CommunicationResource;
use App\Domains\Communications\Services\CommunicationService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommunicationController
{
    use ApiResponse;

    public function __construct(private readonly CommunicationService $service)
    {
    }

    public function index(Request $request, Client $client): JsonResponse
    {
        $paginator = $this->service->paginateForClient($request->user(), $client, $request->only('type'));

        $items = array_map(
            fn (Communication $communication) => (new CommunicationResource($communication))->toArray($request),
            $paginator->items()
        );

        return $this->paginated($paginator->setCollection(collect($items)), 'Communications retrieved');
    }

    public function store(CommunicationStoreRequest $request, Client $client): JsonResponse
    {
        $communication = $this->service->create($request->user(), $client, $request->validated());

        return $this->success(new CommunicationResource($communication), 'Communication logged', 201);
    }

    public function show(Request $request, Client $client, Communication $communication): JsonResponse
    {
        Gate::authorize('view', [$communication, $client]);

        return $this->success(new CommunicationResource($communication->load('creator')));
    }

    public function update(CommunicationUpdateRequest $request, Client $client, Communication $communication): JsonResponse
    {
        $communication = $this->service->update($request->user(), $client, $communication, $request->validated());

        return $this->success(new CommunicationResource($communication), 'Communication updated');
    }

    public function destroy(Request $request, Client $client, Communication $communication): JsonResponse
    {
        $this->service->delete($request->user(), $client, $communication);

        return $this->success(null, 'Communication deleted', 204);
    }
}
