<?php

namespace RZP\Services;

use Illuminate\Http\Response;
use Razorpay\Trace\Logger as Trace;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\DTO\AffordabilityServiceConfig;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class AffordabilityService
{
    public const INVALIDATE_CACHE_ENDPOINT = '/twirp/rzp.checkout.affordability.webhook.v1.AffordabilityWebhookApi/InvalidateCache';

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

    /**
     * Invalidate cache in checkout-affordability-api by making an HTTP request to the microservice.
     *
     * @param array $keys
     *
     * @return bool
     */
    public function invalidateCache(array $keys, string $merchantId = null, bool $InvalidateTerminalCache = false, bool $InvalidateMerchantMethodsCache = false, string $terminalMethod = null): bool
    {
        if (empty($keys) && $merchantId == null) {
            return true;
        }

        $url = $this->baseUrl . self::INVALIDATE_CACHE_ENDPOINT;

        if(!empty($keys))
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
}
