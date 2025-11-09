<?php

namespace App\Domains\Dashboard\Controllers;

use App\Domains\Dashboard\Services\DashboardService;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    use ApiResponse;

    public function __construct(private readonly DashboardService $service)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->can('dashboard.view')) {
            abort(403, 'This action is unauthorized.');
        }

        $metrics = $this->service->getMetrics($user);

        return $this->success($metrics, 'Dashboard metrics retrieved');
    }
}
