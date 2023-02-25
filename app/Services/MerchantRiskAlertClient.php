<?php

namespace RZP\Services;

use Request;
use Throwable;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use \WpOrg\Requests\Session as Requests_Session;
use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Hooks;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\TwirpException;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;

class MerchantRiskAlertClient
{
    const REQUEST_TIMEOUT = 5000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    const NOTIFY_NON_RISKY_MERCHANT_URL = '/twirp/rzp.merchant_risk_alerts.alert.v1.AlertService/NotifyNonRiskyMerchant';

    const CREATE_MERCHANT_ALERT_URL = '/twirp/rzp.merchant_risk_alerts.alert.v1.AlertService/Create';

    const IDENTIFY_BLACKLIST_COUNTRY_ALERTS = '/twirp/rzp.merchant_risk_alerts.blacklist_ip.v1.BlacklistIpService/IdentifyAndPublishAlerts';

    const GET_RULES_URL = '/twirp/rzp.merchant_risk_alerts.rule.v1.RuleService/FetchMultiple';

    const IDENTIFY_BLACKLIST_COUNTRY_ALERTS_REQUEST_TIMEOUT = 180000;

    const RAS_ROUTE_FEATURE_FLAG = 'ras_route_caller_api_category_%s';

    const SVC_CONFIG_KEY = 'services.merchant_risk_alerts';

    const SVC_NEW_CONFIG_KEY = 'services.merchant_risk_alerts.new';

    const FALLBACK_ENABLED = false;

    const RULES = 'rules';

    /**
     * @var Requests_Session
     */
    public $request;

    /**
     * BasicAuth entity
     * @var BasicAuth
     */
    protected $auth;

    /**
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    public function __construct()
    {
        $this->trace = app('trace');

        $this->auth = app('basicauth');

        $this->razorx = app('razorx');

        $this->mode = app('rzp.mode');
    }

    public function init(string $configKey, int $timeoutMs = null)
    {
        $config = config($configKey);

        $auth = [
            $config['auth']['key'],
            $config['auth']['secret']
        ];

        // Options and authentication for requests.
        $options = [
            'auth'  => $auth,
            'hooks' => new Requests_Hooks(),
        ];

        // This will add extra hook onto options[hooks] for dns resolution to
        // ipV4 only. Doing this for internal services only.
        $hooks = new Hooks($config['url']);
        $hooks->addCurlProperties($options);

        // Sets request timeout in milliseconds via curl options.
        $requestTimeout = $timeoutMs ?? self::REQUEST_TIMEOUT;

        $this->setRequestTimeoutOpts($options, $requestTimeout, self::REQUEST_CONNECT_TIMEOUT);

        // Instantiate a request instance.
        $this->request = new Requests_Session(
            $config['url'],
            // Common headers for requests.
            [
                'X-Request-ID' => Request::getTaskId(),
                'Content-Type' => 'application/json',
            ],
            [],
            $options
        );
    }

    /**
     * @param array $options
     * @param int   $timeoutMs
     * @param int   $connectTimeoutMs
     */
    public function setRequestTimeoutOpts(array &$options, int $timeoutMs, int $connectTimeoutMs)
    {
        $options += [
            'timeout'         => $timeoutMs,
            'connect_timeout' => $connectTimeoutMs,
        ];

        // Additionally sets request timeout in milliseconds via curl options.
        $options['hooks']->register(
            'curl.before_send',
            function ($curl) use ($timeoutMs, $connectTimeoutMs)
            {
                curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeoutMs);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeoutMs);
            });
    }

    public function notifyNonRiskyMerchant(string $merchantId)
    {
        $retryWithFallbackRoute = false;

        do {
            $configKey = self::SVC_NEW_CONFIG_KEY;

            $this->init($configKey);

            $requestPayload = ['merchant_id' => $merchantId];

            try {
                $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                    'payload'   => $requestPayload,
                    'service'   => 'merchant_risk_alerts',
                ]);

                return $this->requestAndGetParsedBody(self::NOTIFY_NON_RISKY_MERCHANT_URL, $requestPayload);
            }
            catch (\Throwable $e) {
                $this->trace->traceException($e, Trace::CRITICAL,
                    TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                    [
                        'payload'   => $requestPayload,
                        'service'   => 'merchant_risk_alerts',
                        'path'      => self::NOTIFY_NON_RISKY_MERCHANT_URL,
                    ]
                );

                $retryWithFallbackRoute = $this->retryWithFallback($configKey);
            }
        } while ($retryWithFallbackRoute === true);
    }

    public function createMerchantAlert(
        string $merchantId, string $entityType, string $entityId,
        string $category, string $source, string $eventType,
        int $eventTimestamp, ?array $data)
    {
        $retryWithFallbackRoute = false;

        do {
            $configKey = self::SVC_NEW_CONFIG_KEY;

            $this->init($configKey);

            $requestPayload = [
                'merchant_id'     => $merchantId,
                'entity_type'     => $entityType,
                'entity_id'       => $entityId,
                'category'        => $category,
                'source'          => $source,
                'event_type'      => $eventType,
                'event_timestamp' => $eventTimestamp,
                'data'            => $data,
            ];

            try {
                $data = $requestPayload['data'];

                $requestPayload['data'] = [];

                $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                    'payload'   => $requestPayload,
                    'service'   => 'merchant_risk_alerts',
                ]);

                $requestPayload['data'] = $data;

                return $this->requestAndGetParsedBody(self::CREATE_MERCHANT_ALERT_URL, $requestPayload);
            }
            catch (\Throwable $e) {
                $data = $requestPayload['data'];

                $requestPayload['data'] = [];

                $this->trace->traceException($e, Trace::CRITICAL,
                    TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                    [
                        'payload'   => $requestPayload,
                        'service'   => 'merchant_risk_alerts',
                        'path'      => self::CREATE_MERCHANT_ALERT_URL,
                    ]
                );

                $requestPayload['data'] = $data;

                $retryWithFallbackRoute = $this->retryWithFallback($configKey);
            }
        } while ($retryWithFallbackRoute === true);
    }

    public function sendRequest($url, $requestPayload)
    {
        $retryWithFallbackRoute = false;

        do {
            $configKey = self::SVC_NEW_CONFIG_KEY;

            $this->init($configKey);

            try
            {
                $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                    'payload'   => $requestPayload,
                    'service'   => 'merchant_risk_alerts',
                ]);

                return $this->requestAndGetParsedBody($url, $requestPayload);
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException($e, Trace::CRITICAL,
                                             TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                                             [
                                                 'payload'   => $requestPayload,
                                                 'service'   => 'merchant_risk_alerts',
                                                 'path'      => $url,
                                             ]
                );

                $retryWithFallbackRoute = $this->retryWithFallback($configKey);
            }
        } while ($retryWithFallbackRoute === true);
    }

    public function identifyBlacklistCountryAlerts(array $requestPayload)
    {
        $retryWithFallbackRoute = false;

        do {
            $configKey = self::SVC_NEW_CONFIG_KEY;

            $this->init($configKey, self::IDENTIFY_BLACKLIST_COUNTRY_ALERTS_REQUEST_TIMEOUT);

            try {
                $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                    'payload'   => $requestPayload,
                    'service'   => 'merchant_risk_alerts',
                    'path'      => self::IDENTIFY_BLACKLIST_COUNTRY_ALERTS,
                ]);

                return $this->requestAndGetParsedBody(self::IDENTIFY_BLACKLIST_COUNTRY_ALERTS, $requestPayload);
            }
            catch (\Throwable $e) {
                $this->trace->traceException($e, Trace::CRITICAL,
                    TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                    [
                        'payload'   => $requestPayload,
                        'service'   => 'merchant_risk_alerts',
                        'path'      => self::IDENTIFY_BLACKLIST_COUNTRY_ALERTS,
                    ]
                );

                $retryWithFallbackRoute = $this->retryWithFallback($configKey);
            }
        } while ($retryWithFallbackRoute === true);
    }

    /**
     * @param  string $path
     * @param  array  $payload
     * @return array
     * @throws ServerErrorException
     * @throws TwirpException
     */
    public function requestAndGetParsedBody(string $path, array $payload): array
    {
        $res = $this->request($path, $payload);

        // Returns parsed body..
        $parsedBody = json_decode($res->body, true);
        if (json_last_error() === JSON_ERROR_NONE)
        {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
                'response'   => $parsedBody,
                'service'   => 'merchant_risk_alerts'
            ]);
            return $parsedBody;
        }

        // Else throws exception.
        throw new ServerErrorException(
            'Received invalid response body',
            ErrorCode::SERVER_ERROR_MERCHANT_RISK_ALERTS_FAILURE,
            ['path' => $path, 'body' => $res->body]
        );
    }

    /**
     * @param  string $path
     * @param  array  $payload
     * @return \WpOrg\Requests\Response
     * @throws ServerErrorException
     * @throws TwirpException
     */

    public function request(string $path, array $payload, int $timeoutMs = null)
    {
        $options = [];
        if ($timeoutMs !== null)
        {
            $options = ['hooks' => new Requests_Hooks()];
            $this->setRequestTimeoutOpts($options, $timeoutMs, $timeoutMs);
        }

        $res = null;
        $exception = null;
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload), $options);
            }
            catch (Throwable $e)
            {
                $this->trace->traceException($e);
                $exception = $e;
                continue;
            }

            // In case it succeeds in another attempt.
            $exception = null;
            break;
        }

        // An exception is thrown by lib in cases of network errors e.g. timeout etc.
        if ($exception !== null)
        {
            throw new ServerErrorException(
                'Failed to complete request',
                ErrorCode::SERVER_ERROR_MERCHANT_RISK_ALERTS_FAILURE,
                ['path' => $path],
                $exception
            );
        }
        // If response was received but was not a success e.g. 4XX, 5XX, etc then
        // throws a wrapped exception so api renders it in response properly.
        if ($res->success === false)
        {
            throw new TwirpException(json_decode($res->body, true));
        }

        return $res;
    }

    public function fetchMultiple(string $entity, array $input)
    {
        switch ($entity)
        {
            case self::
            RULES:
                return $this->getRules($input);
        }

        return [];
    }

    public function getRules(array $input)
    {
        $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
            'input'   => $input,
            'service'   => 'merchant_risk_alerts'
        ]);

        return $this->sendRequest(self::GET_RULES_URL, $input);
    }

    protected function getRasConfigKey(string $category, string $treatmentId, bool $tryWithFallback, array $logData)
    {
        if ($tryWithFallback === true)
        {
            return self::SVC_CONFIG_KEY;
        }

        $featureFlag = sprintf(self::RAS_ROUTE_FEATURE_FLAG, $category);

        $variant = $this->razorx->getTreatment($treatmentId, $featureFlag, $this->mode);

        $this->trace->info(TraceCode::MERCHANT_RISK_ALERT_SERVICE_ROUTE_RAZORX_VARIANT, [
            'treatment_id'   => $treatmentId,
            'razorx_variant' => $variant,
            'extra_data'     => $logData,
        ]);

        if ($variant === 'on')
        {
            return self::SVC_NEW_CONFIG_KEY;
        }

        return self::SVC_CONFIG_KEY;
    }

    protected function retryWithFallback($triedWithSvcConfigKey)
    {
        return ($triedWithSvcConfigKey === self::SVC_NEW_CONFIG_KEY) && (self::FALLBACK_ENABLED === true);
    }
}
