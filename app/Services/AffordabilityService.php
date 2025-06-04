<?php

namespace RZP\Services;

use Illuminate\Http\Response;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\DTO\AffordabilityServiceConfig;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;
use RZP\Exception;

class AffordabilityService
{
    public const INVALIDATE_CACHE_ENDPOINT = '/twirp/rzp.checkout.affordability.webhook.v1.AffordabilityWebhookApi/InvalidateCache';

    public const CREATE_EMI_PLANS_ENDPOINT = '/v1/emi_plans';

    /** @var string The ingress url of checkout-affordability-api */
    protected $baseUrl;

    /** @var string */
    protected $mode;

    /** @var string Internal auth secret of checkout-affordability-api */
    protected $secret;

    /** @var Trace */
    protected $trace;

    public function __construct(Trace $trace, AffordabilityServiceConfig $config)
    {
        $this->baseUrl = $config->getBaseUrl();

        $this->secret = $config->getSecret();

        $this->mode = $config->getMode();

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
            $errorDescription = "Unable to create EMI Plans due to bad request";
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
