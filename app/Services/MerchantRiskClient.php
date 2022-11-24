<?php

namespace RZP\Services;

use Request;
use Throwable;
use \WpOrg\Requests\Hooks as Requests_Hooks;
use \WpOrg\Requests\Session as Requests_Session;
use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Http\Request\Hooks;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\TwirpException;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;

class MerchantRiskClient
{

    const REQUEST_TIMEOUT = 4000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    const ENQUEUE_PROFANITY_CHECKER = "/twirp/rzp.merchants_risk.profanityChecker.v1.ProfanityCheckerService/EnqueueRequest";
    const CHECK_IMPERSONATION_PATH  = "/twirp/rzp.merchants_risk.impersonation.v1.ImpersonationService/Match";
    const GET_IMPERSONATION_PATH    = "/twirp/rzp.merchants_risk.impersonation.v1.ImpersonationService/GetDetails";
    const ALERT_SERVICE_PATH        = "/twirp/rzp.merchant_risk_alerts.alert.v1.AlertService/Create";

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
        $config = config('services.merchants_risk');

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

    public function getMerchantRiskScores(string $clientType, string $entityId, array $fields)
    {
        $this->init();

        $requestPayload = [
            "client_type" => $clientType,
            "entity_id" => $entityId,
            "fields" => $fields
        ];

        $requestLog = [
            "client_type" => $clientType,
            "entity_id" => $entityId,
            "fields" => array_column($fields, "field")
        ];

        try {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                'payload'   => $requestLog,
                'service'   => 'merchants-risk'
            ]);

            return $this->requestAndGetParsedBody(self::CHECK_IMPERSONATION_PATH, $requestPayload);
        }
        catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::CRITICAL,
                TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                [
                    'payload'   => $requestLog,
                    'service'   => 'merchants-risk',
                    'path'      => ''
                ]
            );
        }
    }

    public function getMerchantImpersonatedDetails(string $clientType, string $entityId)
    {
        $this->init();

        $requestPayload = [
            "client_type" => $clientType,
            "entity_id" => $entityId
        ];

        try {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_GET_REQUEST, [
                'payload'   => $requestPayload,
                'service'   => 'merchants-risk'
            ]);

            return $this->requestAndGetParsedBody(self::GET_IMPERSONATION_PATH, $requestPayload);
        }
        catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::CRITICAL,
                TraceCode::DOWNSTREAM_SERVICE_GET_REQUEST_FAILED,
                [
                    'payload'   => $requestPayload,
                    'service'   => 'merchants-risk',
                    'path'      => ''
                ]
            );
        }
    }

    public function enqueueProfanityCheckerRequest(string $merchantId, string $moderationType, string $entityType, string $entityId, string $target, int $depth, string $caller = null): array
    {
        $this->init();

        $requestPayload = [
            'MerchantId'     => $merchantId,
            'ModerationType' => $moderationType,
            'EntityType'     => $entityType,
            'EntityId'       => $entityId,
            'Caller'         => $caller ?? $this->auth->getInternalApp(),
        ];

        if ($moderationType === 'text')
        {
            $requestPayload['Text'] = $target;
        }
        else if ($moderationType === 'image')
        {
            $requestPayload['URL'] = $target;
        }
        else if ($moderationType === 'site')
        {
            $requestPayload['URL'] = $target;

            $requestPayload['Depth'] = $depth;
        }

        try
        {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST, [
                'payload' => $requestPayload,
                'service' => 'merchants-risk',
                'path'    => self::ENQUEUE_PROFANITY_CHECKER,
            ]);

            return $this->requestAndGetParsedBody(self::ENQUEUE_PROFANITY_CHECKER, $requestPayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL, TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED);

            return [];
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

        $bodyLog = $parsedBody;

        if (isset($bodyLog["fields"]) === true)
        {
            $bodyLog["fields"] = array_column($bodyLog["fields"], "field");
        }

        if (json_last_error() === JSON_ERROR_NONE)
        {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RESPONSE, [
                'response'   => $bodyLog,
                'service'   => 'merchants-risk'
            ]);
            return $parsedBody;
        }

        if ($res->status_code === 404)
        {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_RECORD_NOT_FOUND, [
                'response'   => $bodyLog,
                'service'   => 'merchants-risk'
            ]);

            return [];
        }

        // Else throws exception.
        throw new ServerErrorException(
            'Received invalid response body',
            ErrorCode::SERVER_ERROR_MERCHANT_RISKS_FAILURE,
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
                "Failed to complete request",
                ErrorCode::SERVER_ERROR_MERCHANT_RISKS_FAILURE,
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

    public function validateRiskFactorForMerchantRequest(array $requestPayload)
    {
        $this->init();

        $response = [];
        try
        {
            $this->trace->info(TraceCode::DOWNSTREAM_SERVICE_REQUEST,
                               [
                                   'payload' => $requestPayload,
                                   'service' => 'merchants-risk'
                               ]);

            $response = $this->requestAndGetParsedBody(self::CHECK_IMPERSONATION_PATH, $requestPayload);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                                         Trace::CRITICAL,
                                         TraceCode::DOWNSTREAM_SERVICE_REQUEST_FAILED,
                                         [
                                             'payload' => $requestPayload,
                                             'service' => 'merchants-risk',
                                             'path'    => self::CHECK_IMPERSONATION_PATH,
                                         ]
            );
        }

        return $response;
    }

    public function createAlertRequest(array $requestPayload)
    {
        $this->init();

        $response = [];
        try
        {
            $this->trace->info(TraceCode::DOWNSTREAM_ALERT_SERVICE_REQUEST,
                [
                    'payload' => $requestPayload,
                    'service' => 'merchants-alerts'
                ]);

            $response = $this->requestAndGetParsedBody(self::ALERT_SERVICE_PATH, $requestPayload);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::CRITICAL,
                TraceCode::DOWNSTREAM_ALERT_SERVICE_REQUEST_FAILED,
                [
                    'payload' => $requestPayload,
                    'service' => 'merchants-alerts',
                    'path'    => self::ALERT_SERVICE_PATH,
                ]
            );
        }

        return $response;
    }
}
