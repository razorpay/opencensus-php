<?php

namespace RZP\Services;
use App;
use Illuminate\Http\Response;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Exception;
use Request;

class AffordabilityService
{
    public const INVALIDATE_CACHE_ENDPOINT = '/twirp/rzp.checkout.affordability.webhook.v1.AffordabilityWebhookApi/InvalidateCache';

    public const CREATE_EMI_PLANS_ENDPOINT = '/v1/emi_plans';
    const CONTENT_TYPE_HEADER = 'Content-Type';
    const X_RAZORPAY_TASKID_HEADER      = 'X-Razorpay-TaskId';

    /** @var string The ingress url of checkout-affordability-api */
    protected $baseUrl;

    const APPLICATION_JSON              = 'application/json';

    const MAX_RETRY_COUNT = 1;

    // Headers
    const ACCEPT            = 'Accept';
    const CONTENT_TYPE      = 'Content-Type';
    const X_TASK_ID         = 'X-Task-Id';

    /** @var string */
    protected $mode;

    /** @var string Internal auth secret of checkout-affordability-api */
    protected $secret;

    /** @var Trace */
    protected $trace;

    protected $app;

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $config =  $app['config']->get('applications.affordability');

        $trace =  $app['trace'];

        $this->baseUrl =  $config['url'];

        $this->secret = $config['service_secret'];

        $this->mode = $app['rzp.mode'] ?? ($app->runningUnitTests() ? Mode::TEST : Mode::LIVE);

        $this->trace = $trace;
    }

    public function getBaseURL(): string {
        return $this->baseUrl;
    }

    public function addOfflineEmiPlan($input): array {
        $url = $this->baseUrl . self::CREATE_EMI_PLANS_ENDPOINT;

        try {
            $response = Requests::post($url, ['Content-Type'  => 'application/json'], json_encode($input));
            return $this->formatResponse($response);
        } catch (Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CHECKOUT_AFFORDABILITY_API_SERVICE_EMI_PLANS_CREATE_FAILED
            );

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }
    }
    public function addEmiPlan( $emiPlanData)
    {
        $url = '/v1/emi_plans';

        $response = $this->sendRequest('POST', $url, $emiPlanData);

        return $response;
    }

    public function getEmiPlan(string $emiPlanId)
    {
        $url = '/v1/emi_plans/' . $emiPlanId;

        $response = $this->sendRequest('GET', $url);

        return $response;
    }

    public function deleteEmiPlan(string $emiPlanId)
    {
        $url = '/v1/emi_plans/' . $emiPlanId;

        $response = $this->sendRequest('DELETE', $url);

        return $response;
    }

    public function sendRequest(string $method, string $url, array $data = [])
    {
        $request = [
            'url'     => $this->getBaseUrl() . $url,
            'method'  => $method,
            'content' => $data,
            'headers' => $this->getDefaultHeaders()
        ];

        $this->trace->info(TraceCode::CHECKOUT_AFFORDABILITY_SERVICE_REQUEST, $request);

        $response = $this->sendRawRequest($request);

        $response = $this->formatResponse($response);

        $this->trace->info(TraceCode::CHECKOUT_AFFORDABILITY_SERVICE_RESPONSE, $response ?? []);

        return $response;
    }

    protected function sendRawRequest($request)
    {
        $retryCount = 0;

        while (true)
        {
            try
            {
                $content = $request['content'];

                if ($request['method'] === 'POST')
                {
                    $content = json_encode($request['content']);
                }

                $response = Requests::request(
                    $request['url'],
                    $request['headers'],
                    $content,
                    $request['method']
                );
                break;
            }
            catch(\WpOrg\Requests\Exception $e)
            {
                $this->trace->traceException($e);

                if ($retryCount < self::MAX_RETRY_COUNT)
                {
                    $this->trace->info(
                        TraceCode::CHECKOUT_AFFORDABILITY_SERVICE_RETRY,
                        [
                            'message' => $e->getMessage(),
                            'type'    => $e->getType(),
                            'data'    => $e->getData()
                        ]);

                    $retryCount++;

                    continue;
                }

                $this->throwServiceErrorException($e);
            }
        }

        return $response;
    }

    protected function throwServiceErrorException(\Throwable $e)
    {
        $errorCode = ErrorCode::SERVER_ERROR_CHECKOUT_AFFORDABILITY_SERVICE_FAILURE;

        if ((empty($e->getData()) === false) and
            (curl_errno($e->getData()) === CURLE_OPERATION_TIMEDOUT))
        {
            $errorCode = ErrorCode::SERVER_ERROR_CHECKOUT_AFFORDABILITY_SERVICE_TIMEOUT;
        }

        throw new Exception\ServerErrorException($e->getMessage(), $errorCode);
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            self::CONTENT_TYPE_HEADER      => self::APPLICATION_JSON,
            self::X_RAZORPAY_TASKID_HEADER => $this->app['request']->getTaskId(),
        ];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';

        if(!empty(Request::header(RequestHeader::DEV_SERVE_USER))){
            $headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
        }

        return $headers;
    }


    /**
     * Invalidate cache in checkout-affordability-api by making an HTTP request to the microservice.
     *
     * @param array $keys
     *
     * @return bool
     */
    public function invalidateCache(array $keys, bool $invalidateOffersCacheForAllMerchants = false, string $merchantId = null, bool $InvalidateTerminalCache = false, bool $InvalidateMerchantMethodsCache = false, string $terminalMethod = null): bool
    {
        if (empty($keys) && $merchantId == null && !$invalidateOffersCacheForAllMerchants) {
            return true;
        }

        $url = $this->baseUrl . self::INVALIDATE_CACHE_ENDPOINT;

        if($invalidateOffersCacheForAllMerchants) {
            $data = ['invalidate_offers_cache_for_all_merchants' => $invalidateOffersCacheForAllMerchants];
        }
        else if(!empty($keys))
        {
            $data = ['merchant_keys' => $keys];
        }
        else // for eligibility cache invalidation
        {
            $data = ['merchant_id' => $merchantId,'invalidate_terminal_cache' => $InvalidateTerminalCache,'invalidate_merchant_methods_cache' => $InvalidateMerchantMethodsCache, 'terminal_method' => $terminalMethod ];
        }

        try {
            $response = Requests::post($url, $this->getRequestHeaders(), json_encode($data));
        } catch (Requests_Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::AFFORDABILITY_INVALIDATE_CACHE_REQUEST_FAILED
            );

            return false;
        }

        return in_array($response->status_code, [Response::HTTP_OK, Response::HTTP_NO_CONTENT], true);
    }

    protected function getRequestHeaders(): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic '. $this->getInternalAuthToken()
        ];
    }

    protected function getInternalAuthToken(): string
    {
        $username = 'rzp_live';
        $password = $this->secret;

        return base64_encode("{$username}:{$password}");
    }

    protected function formatResponse($response)
    {
        if ($response->status_code >= 500) {

            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);

        } else if ($response->status_code >= 400) {

            $errorResponse = json_decode($response->body);
            $errorDescription = "Bad request error";
            if(isset($errorResponse->errorMessage)) {
                $errorDescription = $errorResponse->errorMessage;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null, $errorDescription);
        }

        if ($response->body === "null" or $response->body === '') {
            throw new Exception\ServerErrorException('Error completing the request',
                ErrorCode::SERVER_ERROR);
        }

        return json_decode($response->body, true);
    }
}
