<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Http\RequestHeader;

class CODEligibilityAttributeController extends Controller
{

    protected function bulkUpsert($codEligibilityType)
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $userEmail = $this->ba->getUser()->getEmail();

        $response = $this->app['rto_prediction_provider_service']->bulkUpsert($input, $merchantId, $userEmail, $codEligibilityType);

        return ApiResponse::json($response);
    }

    protected function list($codEligibilityType)
    {
        $input = Request::all();

        $merchantId = $this->ba->getMerchant()->getId();

        $response = $this->app['rto_prediction_provider_service']->list($input, $merchantId, $codEligibilityType);

        return ApiResponse::json($response);
    }

    protected function delete($id)
    {
        $merchantId = $this->ba->getMerchant()->getId();

        return $this->app['rto_prediction_provider_service']->delete($id, $merchantId);
    }

    protected function batchUpsert($codEligibilityType)
    {
        $input = Request::all();

        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID);

        $userId = $this->app['request']->header(RequestHeader::X_Creator_Id);

        $userEmail = $this->repo->user->findOrFail($userId)->getEmail();

        $response = $this->app['rto_prediction_provider_service']->batchUpsert($input, $merchantId, $userEmail, $codEligibilityType);

        return ApiResponse::json($response);
    }

    protected function deleteByAttribute($codEligibilityType, $attributeType, $attributeValue)
    {
        $merchantId = $this->ba->getMerchant()->getId();

        return $this->app['rto_prediction_provider_service']->deleteByAttribute($merchantId, $codEligibilityType, $attributeType, $attributeValue);
    }

}
