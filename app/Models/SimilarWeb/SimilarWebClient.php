<?php

namespace RZP\Models\SimilarWeb;

use Request;
use Throwable;
use \WpOrg\Requests\Session as Requests_Session;
use \WpOrg\Requests\Response;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\ServerErrorException;

class SimilarWebClient
{

    public $request;

    protected $trace;

    private $config;

    protected $path;

    public function __construct($token = null, $path = null)
    {
        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_SIMILARWEB);
        //$this->request = new Request();
    }

    public function init()
    {
        // Options for requests.
        $options = [
        ];

        $headers = [
            Constants::CONTENT_TYPE => Constants::APPLICATION_JSON,
            Constants::ACCEPT => Constants::APPLICATION_JSON,
        ];

        // Instantiate a request instance.
        $this->request = new Requests_Session(
            $this->config[Constants::URL],
            // Common headers for requests.
            $headers,
            [],
            $options
        );
    }

    public function getDetails(SimilarWebRequest $request): ?SimilarWebResponse
    {
        try {
            $this->init();

            $requestArray = (array)$request;

            unset($requestArray['domain']);

            $this->trace->info(TraceCode::SIMILARWEB_REQUEST, [
                Constants::PAYLOAD  => $requestArray,
                Constants::DOMAIN   => $request->domain
            ]);

            $requestArray[Constants::API_KEY] = $this->config[Constants::API_KEY];

            $response = $this->requestAndGetParsedBody($request->getPath(), $requestArray);

            return new SimilarWebResponse($response);

        } catch (\Throwable $e) {
            $this->trace->traceException($e, Trace::CRITICAL,
                TraceCode::SIMILARWEB_REQUEST_FAILURE,
                [
                    Constants::PAYLOAD => $requestArray,
                    Constants::PATH => $request->getPath(),
                    Constants::ERROR => $e->getMessage()
                ]
            );

            return new SimilarWebResponse([Constants::STATUS=>Constants::ERROR,
                                            Constants::ERROR_MSG=>$e->getMessage()]);
        }
    }

    /**
     * @param string $path
     * @param array $payload
     *
     * @return array
     * @throws ServerErrorException
     */
    public function requestAndGetParsedBody(string $path, array $payload): array
    {
        /*
         * Sample Request -
         *
         * None of the fields are sent in body, everything is query parameter
         *
         * {
            'api_key'           :{API_KEY}
            'start_date'        :2022-06
            'end_date'          :2022-06
            'country'           :in
            'granularity'       :monthly
            'main_domain_only'  :false
            'format'            :json
            'show_verified'     :false
            'mtd'               :false
         * }
         *
         * Sample Response -
         *
         * {
                "meta": {
                    "request": {
                        "granularity": "Monthly",
                        "main_domain_only": false,
                        "mtd": false,
                        "show_verified": false,
                        "state": null,
                        "format": "json",
                        "domain": "razorpay.com",
                        "start_date": "2022-06-01",
                        "end_date": "2022-06-30",
                        "country": "in"
                    },
                    "status": "Success",
                    "last_updated": "2022-08-31"
                },
                "visits": [
                    {
                        "date": "2022-06-01",
                        "visits": 16731909.160165433
                    }
                ]
            }
         */

        try {
            $res = $this->request($path, $payload);
        } catch (ServerErrorException $e) {
            throw $e;
        }

        // Returns parsed body..
        $bodyLog = json_decode($res->body, true);

        $parsedBody = [];
        $parsedBody[Constants::STATUS] =  $bodyLog['meta']['status'];

        if (isset($bodyLog["visits"]) === true)
        {
            $parsedBody[Constants::VISITS] = (int)array_column($bodyLog["visits"], "visits")[0];
        }

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->trace->info(TraceCode::SIMILARWEB_RESPONSE, [
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
     * @param string $path
     * @param array $payload
     *
     * @return \WpOrg\Requests\Response
     * @throws ServerErrorException
     */

    public function request(string $path, array $payload)
    {
        $res = null;
        $exception = null;
        $maxAttempts = 2;

        while ($maxAttempts--) {
            try {

                $res = $this->request->request($path, [], $payload);

            } catch (Throwable $e) {
                $this->trace->traceException($e);
                $exception = $e;
                continue;
            }

            // In case it succeeds in another attempt.
            $exception = null;
            break;
        }

        // An exception is thrown by lib in cases of network errors e.g. timeout etc.
        if ($exception !== null) {
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR,
                [Constants::PATH => $path],
                $exception
            );
        }
        // If response was received but was not a success e.g. 4XX, 5XX, etc then
        // throws a wrapped exception so api renders it in response properly.
        if ($res->success === false) {
            if ($res->status_code == 400) {
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
