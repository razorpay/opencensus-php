<?php

namespace RZP\Services;

use App;
use Exception;
use Illuminate\Http\Response;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class CheckoutService
{
    public const PREFERENCES_ENDPOINT = '/v1/preferences';

    /** @var string The ingress url of checkout-service */
    protected string $baseUrl;

    /** @var Trace */
    protected $trace;

    protected $auth;

    const TIMEOUT = 'timeout';

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-Id';
    const X_REQUEST_TASK_ID = 'X-Razorpay-TaskId';
    const X_SERVICE_NAME    = 'X-Service-Name';
    const COOKIE            = 'Cookie';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.checkout_service');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];

        $this->auth = $app['basicauth'];
    }

    /**
     * Get checkout preferences from checkout service
     *
     * @param  array  $input
     * @return Response
     * @throws Exception
     */
    public function getCheckoutPreferencesFromCheckoutService(array $input): Response
    {
        $urlPath  = self::PREFERENCES_ENDPOINT . '?' . http_build_query($input, '', '&');

        try {
            return $this->sendRequest($urlPath, [], Requests::GET);
        } catch (Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CHECKOUT_SERVICE_PREFERENCES_REQUEST_FAILURE
            );

            throw $e;
        }
    }

    public function sendRequest($path, $input, $method): Response
    {
        $url = $this->baseUrl . $path;

        try
        {
            $this->trace->info(TraceCode::CHECKOUT_SERVICE_REQUEST, [
                'url'   => $url,
                'method' => $method,
            ]);

            $input = (empty($input) === false) ? json_encode($input) : null;

            $response = Requests::request(
                $url,
                $this->getHeaders(),
                $input,
                $method,
                $this->getOptions()
            );

            $parsedResponse = $this->parseAndReturnResponse($response);

            if ($response->status_code !== 200) {
                $this->trace->error(
                    TraceCode::CHECKOUT_SERVICE_REQUEST_FAILURE,
                    [
                        'status_code' => $response->status_code,
                        'parsed_response' => $parsedResponse,
                        'url' => $url
                    ]
                );
            }

            if ($response->status_code === 400) {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, $parsedResponse);
            }

            if($response->status_code >= 400) {
                throw new ServerErrorException(
                    TraceCode::CHECKOUT_SERVICE_REQUEST_FAILURE,
                    ErrorCode::SERVER_ERROR,
                    $parsedResponse
                );
            }

            $responseHeaders = $this->getHeadersFromRawResponse($response->raw);

            return new Response($parsedResponse, 200, $responseHeaders);
        }
        catch (Exception $ex)
        {
            $this->trace->count(TraceCode::CHECKOUT_SERVICE_REQUEST_ERROR);

            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::CHECKOUT_SERVICE_REQUEST_ERROR,
                ['url' => $url]
            );

            throw $ex;
        }
    }


    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        return $responseArray ?? [];
    }

    /**
     * Function used to get headers for the request
     */
    protected function getHeaders(): array
    {
        $headers = [
            self::ACCEPT                => 'application/json',
            self::CONTENT_TYPE          => 'application/json',
            self::X_REQUEST_ID          => $this->request->getId(),
            self::X_REQUEST_TASK_ID     => $this->request->getTaskId(),
            self::X_SERVICE_NAME        => 'api',
        ];

        $cookieHeader = $this->request->headers->get(self::COOKIE);
        if ($cookieHeader !== null) {
            $headers[self::COOKIE] = $cookieHeader;
        }

        return $headers;
    }

    protected function getOptions(): array
    {
        return [
            self::TIMEOUT => $this->config['timeout'],
        ];
    }

    protected function getHeadersFromRawResponse(string $rawResponse): array
    {
        $headers = [];

        // Extract the headers from the raw response
        $headerString = substr($rawResponse, 0, strpos($rawResponse, "\r\n\r\n"));
        foreach (explode("\r\n", $headerString) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            } else {
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }
}
