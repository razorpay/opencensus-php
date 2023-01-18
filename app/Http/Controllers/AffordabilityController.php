<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RZP\Models\Affordability\Service as AffordabilityService;

class AffordabilityController extends Controller
{
    /** @var AffordabilityService */
    protected $service;

    /**
     * Create a new AffordabilityController instance.
     *
     * @param AffordabilityService $service
     */
    public function __construct(AffordabilityService $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * Fetch the affordability suite components for the given merchant.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::json($this->service->fetchSuite($request->toArray()));
    }

    /**
     * Fetch the affordability widget info for the given merchant.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getWidgetDetails()
    {
        $data = $this->service->getWidgetDetails();

        return ApiResponse::json($data);
    }

    public function updateWidgetTrialPeriod(Request $request)
    {
        $input = $request->all();

        $data = $this->service->updateWidgetTrialPeriod($input);

        return ApiResponse::json($data);
    }
}
