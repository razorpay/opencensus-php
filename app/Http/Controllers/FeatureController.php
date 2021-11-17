<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Feature\Type;
use RZP\Models\Feature\Constants;

class FeatureController extends Controller
{
    /**
     * Adds features to accounts
     *
     * @return \Illuminate\Http\Response
     */
    public function addAccountFeatures()
    {
        $input = Request::all();

        $data = $this->service()->addAccountFeatures($input);

        return ApiResponse::json($data);
    }

    /**
     * Adds LEDGER_JOURNAL_WRITES features to accounts
     * And then onboard that account to ledger service
     *
     * @return ApiResponse
     */
    public function addFeatureAndOnboardOldAccountsToLedger()
    {
        $input = Request::all();

        $data = $this->service()->addFeatureAndOnboardOldAccountsToLedger($input);

        return ApiResponse::json($data->toArrayWithItems());
    }

    /**
     * Adds features to entities
     *
     * @param string|null $routeName
     * @param string|null $entityId
     *
     * @return \Illuminate\Http\Response
     */
    public function addFeatures(string $routeName = null, string $entityId = null)
    {
        $input = Request::all();

        $data = $this->service()->addFeatures($input, $routeName, $entityId);

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
        return $this->deleteEntityFeature(Type::ACCOUNTS, $entityId, $featureName);
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
        return $this->getFeatures(Type::ACCOUNTS, $merchantId);
    }

    /**
     * Returns the features assigned to the merchant
     *
     * @return \Illuminate\Http\Response
     */
    public function getAccountFeatures()
    {
        return $this->getFeatures();
    }


    protected function getFeatureStatus($entityType, $entityId, $featureName)
    {
        $data = $this->service()->checkFeatureEnabled($entityType, $entityId, $featureName);

        return ApiResponse::json($data);
    }

    /**
     * Returns the features assigned to the entity
     *
     * @param string|null $entityType
     * @param string|null $entityId
     *
     * @return \Illuminate\Http\Response
     */
    protected function getFeatures($entityType = null, $entityId = null)
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

    public function suggestedOptInCron()
    {
        $this->service()->handleSuggestedOptIn();
    }

    public function getMerchantIdsHavingFeatures()
    {
        $input = Request::all();

        $response = $this->service()->getMerchantIdsHavingFeatures($input);

        return ApiResponse::json($response);
    }
}
