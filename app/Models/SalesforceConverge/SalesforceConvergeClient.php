<?php

namespace RZP\Models\SalesforceConverge;

use Request;
use Throwable;
use Carbon\Carbon;
use \WpOrg\Requests\Session as Requests_Session;
use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;

class SalesforceConvergeClient
{

    const REQUEST_TIMEOUT = 4000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    public $request;

    protected $trace;

    private $config;

    protected $path;

    public function __construct($token=null, $path=null)
    {
        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_SALESFORCE_CONVERGE);
    }

    public function init($token = null)
    {
        // Options for requests.
        $options = [
        ];

        // Sets request timeout in milliseconds via curl options.
        $this->setRequestTimeoutOpts($options, self::REQUEST_TIMEOUT, self::REQUEST_CONNECT_TIMEOUT);

        $headers = [
            Constants::CONTENT_TYPE => Constants::APPLICATION_JSON,
            Constants::ACCEPT       => Constants::APPLICATION_JSON,
        ];

        if (empty($token) === false)
        {
            $headers[Constants::AUTHORIZATION] = $token->getAuthorizationHeader();
        }
        // Instantiate a request instance.
        $this->request = new Requests_Session(
            $this->config[Constants::URL],
            // Common headers for requests.
            $headers,
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
            Constants::TIMEOUT         => $timeoutMs,
            Constants::CONNECT_TIMEOUT => $connectTimeoutMs,
        ];

    }


    public function getAuthorization(AuthorizationRequest $request): ?AuthToken
    {
        $this->init();
        try
        {
            $this->trace->info(TraceCode::SALESFORCE_CONVERGE_AUTH_REQUEST, [
                Constants::PAYLOAD => (array) $request,
                Constants::PATH    => $request->getPath()
            ]);

            $options = ["data_format" => "query"];

            $requestArray = (array) $request;
            $requestArray["grant_type"] = "password";

            $response = $this->requestAndGetParsedBody($request->getPath(), $requestArray, $options);

            return new AuthToken($response);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL,
                                         TraceCode::SALESFORCE_CONVERGE_AUTH_REQUEST_FAILED,
                                         [
                                             Constants::PATH => $request->getPath()
                                         ]
            );

            return null;
        }
    }

    public function pushUpdates(SalesforceMerchantUpdatesRequest $request, $token): ?EventResponse
    {
        /*
         * Sample Request:
         * {
                "CX_Source__c": "admindashboard",
                "CX_Process__c": "Activation",
                "CX_Payload__c": "{\"merchant_id\":\"Gh987kLjpo\",
                \"activated\":0,\"activated_at\":\"2022-09-20\",\"activation_progress\":50,
                \"activation_status\":\"under_review\",\"activation_flow\":\"whitelist\",
                \"foh\":false,\"lead_score_pg\":85}"
            }
         *
         * Sample Response:
         * {
                "id": "e01xx0000000001AAA",
                "success": true,
                "errors": [
                    {
                        "statusCode": "OPERATION_ENQUEUED",
                        "message": "978f1a49-bcda-4e3b-be90-8935eb2c11f3",
                        "fields": []
                    }
                ]
            }
         * In case of error:
         * [
                {
                    "message": "Session expired or invalid",
                    "errorCode": "INVALID_SESSION_ID"
                }
            ]
         *
         * */

        $this->init($token);

        try
        {
            $sfFormattedRequest = $request->getFormattedRequest();

            $this->trace->info(TraceCode::SALESFORCE_CONVERGE_SERVICE_REQUEST, [
                Constants::PAYLOAD => $sfFormattedRequest,
                Constants::PATH    => $request->getPath()
            ]);

            $response = $this->requestAndGetParsedBody($request->getPath(), $sfFormattedRequest, []);

            return new EventResponse($response);
        }
        catch (\Throwable $e)
        {
            if ($e->getError()->getInternalErrorCode() == ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED)
            {
                throw $e;
            }
            $this->trace->traceException($e, Trace::NOTICE,
                                         TraceCode::SALESFORCE_CONVERGE_SERVICE_REQUEST_FAILED,
                                         [
                                             Constants::PAYLOAD => (array) $request,
                                             Constants::PATH    => $request->getPath(),
                                             Constants::ERROR   => $e->getMessage()
                                         ]
            );

            return new EventResponse([Constants::ERROR, $e->getCode(), Constants::MESSAGE, $e->getMessage()]);
        }
    }

    /**
     * @param string $path
     * @param array  $payload
     *
     * @return array
     * @throws ServerErrorException
     */
    public function requestAndGetParsedBody(string $path, array $payload, array $options): array
    {
        try {
            $res = $this->request($path, $payload, null, $options);
        }
        catch (ServerErrorException $e) {
            throw $e;
        }

        // Returns parsed body..
        $parsedBody = json_decode($res->body, true);

        if (json_last_error() === JSON_ERROR_NONE)
        {
            $this->trace->info(TraceCode::SALESFORCE_CONVERGE_SERVICE_RESPONSE, [
                Constants::RESPONSE => $parsedBody,
            ]);

            return $parsedBody;
        }

        // Else throws exception.
        throw new ServerErrorException(
            'Received invalid response body',
            ErrorCode::SERVER_ERROR,
            [Constants::PATH => $path, Constants::BODY => $res->body]
        );
    }

    /**
     * @param string   $path
     * @param array    $payload
     *
     * @param int|null $timeoutMs
     *
     * @return \WpOrg\Requests\Response
     * @throws ServerErrorException
     */

    public function request(string $path, array $payload, int $timeoutMs = null, array $options = [])
    {
//        if ($timeoutMs !== null)
//        {
//            $this->setRequestTimeoutOpts($options, $timeoutMs, $timeoutMs);
//        }

        $res         = null;
        $exception   = null;
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                $this->trace->info(TraceCode::SALESFORCE_CONVERGE_AUTH_REQUEST, $payload);

                if (empty($options) == true)
                {
                    //Push API Call with JSON encoded payload
                    $res = $this->request->post($path, [], empty($payload) ? '{}' : json_encode($payload), $options);
                }
                else
                {
                    //Authorization API to be called with Payload sent in Query Parameters
                    $res = $this->request->post($path, [], empty($payload) ? '{}' : $payload, $options);
                }
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
                ErrorCode::SERVER_ERROR,
                [Constants::PATH => $path],
                $exception
            );
        }
        // If response was received but was not a success e.g. 4XX, 5XX, etc then
        // throws a wrapped exception so api renders it in response properly.
        if ($res->success === false)
        {
            if ($res->status_code == 401)
            {
                throw new ServerErrorException(
                    'API Key Expired - Unauthorized 401',
                    ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED,
                    []
                );
            }
            if ($res->status_code == 400)
            {
                $parsedBody = json_decode($res->body, true);
                throw new ServerErrorException(
                    $parsedBody[0]["message"],
                    ErrorCode::SERVER_ERROR,
                    []
                );
            }
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR,
                [Constants::PATH => $path]
            );
        }

        return $res;
    }
}
