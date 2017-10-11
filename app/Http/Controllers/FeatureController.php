<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

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
        $input = Request::all();

        $data = $this->service()->deleteFeature($entityId, $featureName, $input);

        return ApiResponse::json($data);
    }

    public function getFeatures(string $entityId)
    {
        $data = $this->service()->getFeatures($entityId);

        return ApiResponse::json($data);
    }

    public function getOnboardingDetails()
    {
        $input = Request::all();

        $response = $this->service()->getOnboardingDetails($input);

        return ApiResponse::json($response);
    }

    public function postOnboardingResponses(string $feature)
    {
        $input = Request::all();

        $response = $this->service()->postOnboardingResponses($input, $feature);

        return ApiResponse::json($response);
    }

    public function updateOnboardingResponses(string $feature)
    {
        $input = Request::all();

        $response = $this->service()->updateOnboardingResponses($input, $feature);

        return ApiResponse::json($response);
    }

    public function getOnboardingResponses(string $feature)
    {
        $response = $this->service()->getOnboardingResponses($feature);

        return ApiResponse::json($response);
    }

    public function getFeatureActivationRequests()
    {
        $input = Request::all();

        $response = $this->service()->getFeatureActivationRequests($input);

        return ApiResponse::json($response);
    }

    public function updateFeatureActivationStatus(string $featureName)
    {
        $input = Request::all();

        $response = $this->service()->updateFeatureActivationStatus($featureName, $input);

        return ApiResponse::json($response);
    }
}
