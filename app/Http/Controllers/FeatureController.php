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

    public function postOnboardingSubmissions(string $feature)
    {
        $input = Request::all();

        $response = $this->service()->postOnboardingSubmissions($input, $feature);

        return ApiResponse::json($response);
    }

    public function updateOnboardingSubmissions(string $feature)
    {
        $input = Request::all();

        $response = $this->service()->updateOnboardingSubmissions($input, $feature);

        return ApiResponse::json($response);
    }

    public function getOnboardingSubmissions(string $feature)
    {
        $response = $this->service()->getOnboardingSubmissions($feature);

        return ApiResponse::json($response);
    }

    /**
     * Deprecated. Added for BC. Remove after dashboard changes.
     *
     * @param string|null $feature
     *
     * @return mixed
     */
    public function getOnboardingSubmissionsDeprecated(string $feature = null)
    {
        $response = $this->service()->getOnboardingSubmissions($feature);

        return ApiResponse::json($response);
    }

    /**
     * This function will be deprecated by getFeatureOnboardingRequests.
     * Currently, maintained for Backward Compatibility
     *
     * @return mixed
     */
    public function getFeatureOnboardingRequestsByStatus()
    {
        $input = Request::all();

        $response = $this->service()->getFeatureOnboardingRequestsByStatus($input);

        return ApiResponse::json($response);
    }

    public function getFeatureOnboardingRequests()
    {
        $input = Request::all();

        $response = $this->service()->getFeatureOnboardingRequests($input);

        return ApiResponse::json($response);
    }

    public function updateFeatureActivationStatus(string $featureName)
    {
        $input = Request::all();

        $response = $this->service()->updateFeatureActivationStatus($featureName, $input);

        return ApiResponse::json($response);
    }

    public function getFeatureActivationStatus(string $featureName)
    {
        $input = Request::all();

        $response = $this->service()->getFeatureActivationStatus($featureName, $input);

        return ApiResponse::json($response);
    }
}
