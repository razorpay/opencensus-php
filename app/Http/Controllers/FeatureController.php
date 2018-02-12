<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Feature\Constants;

class FeatureController extends Controller
{
    /**
     * Assigns features to accounts
     *
     * @param string|null $accountId
     *
     * @return \Illuminate\Http\Response
     */
    public function addAccountFeatures(string $accountId)
    {
        return $this->addFeatures(Constants::MERCHANT, $accountId);
    }

    /**
     * Assigns features to applications
     *
     * @param string|null $accountId
     *
     * @return \Illuminate\Http\Response
     */
    public function addApplicationFeatures(string $accountId)
    {
        return $this->addFeatures(Constants::APPLICATION, $accountId);
    }

    /**
     * Adds features to entities
     *
     * @todo: Remove the default null values once the feature_add route is
     *        removed and change the access modifier to protected.
     *
     * @param string|null $entityType
     * @param string|null $entityId
     *
     * @return \Illuminate\Http\Response
     */
    public function addFeatures(string $entityType = null, string $entityId = null)
    {
        $input = Request::all();

        $data = $this->service()->addFeatures($input, $entityType, $entityId);

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

    /**
     * Deletes the feature association with the merchant
     *
     * @todo: Remove the function once the dashboard is migrated.
     *
     * @deprecated Use deleteEntityFeature instead.
     * @param string $entityId
     * @param string $featureName
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteFeature(string $entityId, string $featureName)
    {
        return $this->deleteEntityFeature(Constants::MERCHANTS, $entityId, $featureName);
    }

    /**
     * Deletes the feature association with an entity
     *
     * @param string $entityType
     * @param string $entityId
     * @param string $featureName
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteEntityFeature(string $entityType, string $entityId, string $featureName)
    {
        $input = Request::all();

        $data = $this->service()->deleteEntityFeature($entityType, $entityId, $featureName, $input);

        return ApiResponse::json($data);
    }

    /**
     * Returns the features assigned to the merchant
     *
     * @deprecated Use getAccountFeatures instead
     * @param string|null $merchantId
     *
     * @return \Illuminate\Http\Response
     */
    public function getMerchantFeatures(string $merchantId)
    {
        return $this->getFeatures(Constants::MERCHANTS, $merchantId);
    }

    /**
     * Returns the features assigned to the account
     *
     * @param string|null $accountId
     *
     * @return \Illuminate\Http\Response
     */
    public function getAccountFeatures(string $accountId)
    {
        return $this->getFeatures(Constants::ACCOUNTS, $accountId);
    }

    /**
     * Returns the features assigned to the application
     *
     * @param string|null $applicationId
     *
     * @return \Illuminate\Http\Response
     */
    public function getApplicationFeatures(string $applicationId)
    {
        return $this->getFeatures(Constants::APPLICATIONS, $applicationId);
    }

    /**
     * Returns the features assigned to the entity
     *
     * @param string $entityType
     * @param string $entityId
     *
     * @return \Illuminate\Http\Response
     */
    protected function getFeatures(string $entityType, string $entityId)
    {
        $data = $this->service()->getFeatures($entityType, $entityId);

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
     * @deprecated Added for BC. Remove after dashboard changes.
     *
     * @param string|null $feature
     *
     * @return \Illuminate\Http\Response
     */
    public function getOnboardingSubmissionsDeprecated(string $feature = null)
    {
        $response = $this->service()->getOnboardingSubmissions($feature);

        return ApiResponse::json($response);
    }

    /**
     * @deprecated by getFeatureOnboardingRequests()
     *
     * @return \Illuminate\Http\Response
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

    public function bulkUpdateFeatureActivationStatus()
    {
        $input = Request::all();

        $response = $this->service()->bulkUpdateFeatureActivationStatus($input);

        return ApiResponse::json($response);
    }
}
