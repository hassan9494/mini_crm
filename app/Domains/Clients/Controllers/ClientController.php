<?php

namespace App\Domains\Clients\Controllers;

use App\Domains\Clients\Models\Client;
use App\Domains\Clients\Requests\ClientStoreRequest;
use App\Domains\Clients\Requests\ClientUpdateRequest;
use App\Domains\Clients\Resources\ClientResource;
use App\Domains\Clients\Services\ClientService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController
{
    use ApiResponse;

    public function __construct(private readonly ClientService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $paginator = $this->service->paginateForUser($user, $request->only('status'), (int) $request->input('per_page', 15));

        $items = array_map(
            fn (Client $client) => (new ClientResource($client))->toArray($request),
            $paginator->items()
        );

        return $this->paginated($paginator->setCollection(collect($items)), 'Clients retrieved');
    }

    public function store(ClientStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $client = $this->service->create($user, $request->validated());

        return $this->success(new ClientResource($client), 'Client created', 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $request->user()->can('view', $client) || abort(403, 'Forbidden');

        return $this->success(new ClientResource($client->load('assignedRep')));
    }

    public function update(ClientUpdateRequest $request, Client $client): JsonResponse
    {
        $user = $request->user();
        $client = $this->service->update($user, $client, $request->validated());

        return $this->success(new ClientResource($client), 'Client updated');
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->service->delete($request->user(), $client);

        return $this->success(null, 'Client deleted', 204);
    }
}
