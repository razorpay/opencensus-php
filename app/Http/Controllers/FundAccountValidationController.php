<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;

class FundAccountValidationController extends Controller
{
    use Traits\HasCrudMethods;

    public function getFavByMerchantIdAndFavId($merchantId, $favId)
    {
        $entity = $this->service()->getFavByMerchantIdAndFavId($favId, $merchantId);

        return ApiResponse::json($entity);
    }

    public function bulkPatchFavAsFailed()
    {
        $input = Request::all();

        $entity = $this->service()->bulkPatchFavAsFailed($input);

        return ApiResponse::json($entity);
    }

    public function validateVpaInternal()
    {
        $input = Request::all();

        $response = $this->service()->validateVpaInternal($input);

        return ApiResponse::json($response);
    }


    public function getFundAccountValidation($id)
    {
        $input = Request::all();

        $entity = $this->service()->fetchFav($id, $input);

        return ApiResponse::json($entity);
    }

    public function getFundAccountValidations()
    {
        $input = Request::all();

        $data = $this->service()->fetchMultiple($input);

        return ApiResponse::json($data);
    }


    public function validateBankAccountInternal()
    {
        $input = Request::all();

        $response = $this->service()->validateBankAccountInternal($input);

        return ApiResponse::json($response);
    }

    public function fetchPricingInfoForFavService()
    {
        $input = Request::all();

        $response = $this->service()->fetchPricingInfoForFavService($input);

        return ApiResponse::json($response);
    }

    public function sendWebhookToMerchant()
    {
        $input = Request::all();

        $response = $this->service()->sendWebhookToMerchant($input);

        return ApiResponse::json($response);
    }

    public function handleCitiBankWebhook()
    {
        $input = Request::all();

        $errorResp = $this->validateCitiRequestToken();

        if ($errorResp !== null)
        {
            return $errorResp;
        }

        $response = $this->service()->handleBankWebhook($input, "citi");

        return ApiResponse::json($response);
    }

    protected function validateCitiRequestToken()
    {
        $headers = Request::header();

        if (empty($headers['xorgtoken']) === true)
        {
            $this->trace->error(TraceCode::CITI_WEBHOOK_INVALID_CALLBACK_DATA, [
                'message'   => 'empty token',
            ]);

            return ApiResponse::json([
                'status'  => 'failed',
                'code'    => 'AUTH_FAILED',
                'message' => 'Authentication failed - missing token',
            ], 401);
        }

        $actualToken = Request::header('xorgtoken');
        $expectedToken = $this->config['applications.citi_webhook.org_token'];

        if (hash_equals($expectedToken, $actualToken) === false)
        {
            $this->trace->error(TraceCode::CITI_WEBHOOK_INVALID_CALLBACK_DATA, [
                'message'   => 'invalid token',
            ]);

            return ApiResponse::json([
                'status'  => 'failed',
                'code'    => 'AUTH_FAILED',
                'message' => 'Authentication failed - invalid token',
            ], 401);
        }

        return null;
    }

}
