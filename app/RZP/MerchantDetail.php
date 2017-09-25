<?php

namespace App\RZP;

use Config;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Auth;

class MerchantDetail extends Entity
{
    public function fetchDetails()
    {
        $error = $response = null;
        try
        {
            $relativeUrl = 'merchant/activation';

            $response = $this->request('GET', $relativeUrl)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }

    public function submitDetails(array $input)
    {
        $error = $response = null;

        try
        {
            $relativeUrl = 'merchant/activation';

            $response = $this->request('POST', $relativeUrl, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }

    public function updateDetailsByAdmin($merchantId, array $input)
    {
        $error = $response = null;

        $adminUser = Auth::guard('api')->user();

        if (empty($adminUser) === false)
        {
            $adminToken = $adminUser->token;

            // For admin auth (heimdall) on API
            ApiRequest::addHeader('X-Admin-Token', $adminToken);
        }

        try
        {
            $relativeUrl = "merchant/activation/$merchantId/update";

            $response = $this->request('PUT', $relativeUrl, $input)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $error = [ $e->getMessage() ];
        }

        return [ $error, $response ];
    }

    protected function getApiCredentials($mode, $merchantId)
    {
        $id = 'rzp_' . $mode . '_' . $merchantId;

        $secret = Config::get('api.auth_pass');

        return [$id, $secret];
    }
}
