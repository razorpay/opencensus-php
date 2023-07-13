<?php

namespace App\RZP;

use Config;
use App\Trace\TraceCode;
use App\Metrics\Constants;
use App\Admin\ApiRequestAny;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as Guzzle;
use Razorpay\Api\Entity as ApiEntity;
use App\Admin\ApiRouteCircuitBreaker;
use Razorpay\Api\Request as ApiRequest;
use Razorpay\Api\Errors\ServerError as ServerError;
use Razorpay\Api\Errors\BadRequestError as BadRequestError;
use Auth;
use Trace;

class MerchantDetail extends Entity
{
    static function millitime(): int
    {
        return round(microtime(true) * 1000);
    }

    public function fetchDetails()
    {
        $error = null;
        $response = null;
        $httpCode = 200;
        $relativeUrl = 'merchant/activation';
        $app = \App::getFacadeRoot();
        $method = \Request::method();
        $currentRouteName = \Route::currentRouteName() ?? 'unknown_route';
        $apiPathName = 'merchant_activation_details';
        $startTime = self::millitime();

        try
        {
            $this->forwardUTMCookies();

            $response = $this->request('GET', $relativeUrl)->toArray();
        }
        catch (\Razorpay\Api\Errors\BadRequestError $e)
        {
            $httpCode = $e->getHttpStatusCode();
            $error = [ $e->getMessage() ];
        }

        $endTime = self::millitime();

        $timeTaken = $endTime - $startTime;

        try
        {
            $dimensions = (new ApiRequestAny())->getApiMetricDimensions($httpCode, $currentRouteName, $apiPathName, $method,
                $timeTaken);

            $app['metrics']->count(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM, Constants::EVENT_COUNT_ONE, $dimensions);

            $app['metrics']->histogram(Constants::METRIC_COUNTER_HTTP_REQUESTS_API_DOWNSTREAM_DURATION, $timeTaken, $dimensions);
        }
        catch (\Throwable $e)
        {
            Trace::info(TraceCode::PUSH_METRICS_FAILED, [
                'message'     => $e->getMessage(),
                'line_number' => $e->getLine()
            ]);
        }

        return [ $error, $response ];
    }

    public function forwardUTMCookies(){
        if (empty($_COOKIE['rzp_utm']) === false) {
            $cookie = $_COOKIE['rzp_utm'];

            $cookie = str_replace('+', '%2B', $cookie);

            ApiRequest::addHeader('cookie', 'rzp_utm=' . $cookie);
        }
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

            $response = $this->request('PUT', $relativeUrl, json_encode($input))->toArray();
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
