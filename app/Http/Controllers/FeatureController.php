<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\Feature\Onboarding\Service as OnboardingService;

class FeatureController extends Controller
{
    public function addFeatures()
    {
        $input = Request::all();

        $data = $this->service()->addFeatures($input);

        return ApiResponse::json($data);
    }

    public function multiAssignFeature()
    {
        $input = Request::all();

        $data = $this->service()->multiAssignFeature($input);

        return ApiResponse::json($data);
    }

    public function multiRemoveFeature()
    {
        $input = Request::all();

        $data = $this->service()->multiRemoveFeature($input);

        return ApiResponse::json($data);
    }

    public function deleteFeature(string $entityId, string $featureName)
    {
        $data = $this->service()->deleteFeature($entityId, $featureName);

        return ApiResponse::json($data);
    }

    public function getFeatures(string $entityId)
    {
        $data = $this->service()->getFeatures($entityId);

        return ApiResponse::json($data);
    }

    public function getQuestions(OnboardingService $service)
    {
        $input = Request::all();

        $response = $service->getQuestions($input);

        return ApiResponse::json($response);
    }

    public function createResponses(OnboardingService $service)
    {
        $input = Request::all();

        $response = $service->createResponses($input);

        return ApiResponse::json($response);
    }

}
