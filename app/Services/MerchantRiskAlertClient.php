<?php

namespace RZP\Services;

use Request;
use Throwable;
use Requests_Hooks;
use Requests_Session;
use Requests_Response;
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
    // Request timeout in milliseconds for all HTTP requests to stork.
    const REQUEST_TIMEOUT = 2000;
    // Request connect timeout in milliseconds for all HTTP requests to stork.
    // Request timeout parameter applies after connection is established.
    const REQUEST_CONNECT_TIMEOUT = 2000;

    const NOTIFY_NON_RISKY_MERCHANT_URL = "/twirp/rzp.merchant_risk_alerts.alert.v1.AlertService/NotifyNonRiskyMerchant";

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
    }

    public function init()
    {
        $config = config('services.merchant_risk_alerts');

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
        $this->setRequestTimeoutOpts($options, self::REQUEST_TIMEOUT, self::REQUEST_CONNECT_TIMEOUT);

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
        $this->init();

        $requestPayload = ["merchant_id" => $merchantId];

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
        }
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
     * @return Requests_Response
     * @throws ServerErrorException
     * @throws TwirpException
     */

    public function request(string $path, array $payload, int $timeoutMs = null): Requests_Response
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
                "Failed to complete request",
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

}
