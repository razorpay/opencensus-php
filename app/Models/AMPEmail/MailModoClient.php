<?php

namespace RZP\Models\AMPEmail;

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
use Illuminate\Foundation\Http\FormRequest;

class MailModoClient
{

    const REQUEST_TIMEOUT = 4000;

    const REQUEST_CONNECT_TIMEOUT = 2000;

    /**
     * @var Requests_Session
     */
    public $request;

    /**
     * @var Trace
     */
    protected $trace;

    private   $config;

    protected $path;

    public function __construct()
    {
        $this->trace = app('trace');

        $this->config = config(Constants::APPLICATIONS_MAILMODO);

    }

    public function triggerEmail(EmailRequest $request): ?EmailResponse
    {
        $this->init($request);

        try
        {
            $this->trace->info(TraceCode::MAILMODO_SERVICE_REQUEST, [
                Constants::PAYLOAD => $request->getPayload(),
                Constants::PATH    => $request->getPath(),
                Constants::HEADERS => $request->getHeaders()
            ]);

            return new EmailResponse($this->requestAndGetParsedBody($request->getPath(), $request->getPayload()));
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::CRITICAL,
                                         TraceCode::MAILMODO_SERVICE_REQUEST_FAILED,
                                         [
                                             Constants::PAYLOAD => $request->getPayload(),
                                             Constants::PATH    => $request->getPath(),
                                             Constants::HEADERS => $request->getHeaders()
                                         ]
            );

            return new EmailResponse([Constants::ERROR   => $e->getCode(),
                                      Constants::MESSAGE => $e->getMessage(),
                                      Constants::SUCCESS => false,
                                      Constants::STATUS  => "error"]);
        }
    }


    public function init(EmailRequest $request)
    {

        // Options for requests.
        $options = [
        ];

        // Sets request timeout in milliseconds via curl options.
        $this->setRequestTimeoutOpts($options, self::REQUEST_TIMEOUT, self::REQUEST_CONNECT_TIMEOUT);

        $headers                                                 = [
            Constants::CONTENT_TYPE => Constants::APPLICATION_JSON,
            Constants::ACCEPT       => Constants::APPLICATION_JSON,
        ];
        $headers[$this->config[Constants::AUTH][Constants::KEY]] = $this->config[Constants::AUTH][Constants::SECRET];

        // Instantiate a request instance.
        $this->request = new Requests_Session(
            $this->config[Constants::URL],
            // Common headers for requests.
            $request->getHeaders(),
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


    /**
     * @param string $path
     * @param array  $payload
     *
     * @return array
     * @throws ServerErrorException
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
            $this->trace->info(TraceCode::MAILMODO_SERVICE_RESPONSE, [
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

    public function request(string $path, array $payload, int $timeoutMs = null)
    {
        $options = [];
        if ($timeoutMs !== null)
        {
            $this->setRequestTimeoutOpts($options, $timeoutMs, $timeoutMs);
        }

        $res         = null;
        $exception   = null;
        $maxAttempts = 2;

        while ($maxAttempts--)
        {
            try
            {
                $this->trace->info(TraceCode::MAILMODO_SERVICE_REQUEST, $payload);

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
                ErrorCode::SERVER_ERROR,
                [Constants::PATH => $path],
                $exception
            );
        }
        // If response was received but was not a success e.g. 4XX, 5XX, etc then
        // throws a wrapped exception so api renders it in response properly.
        if ($res->success === false)
        {
            throw new ServerErrorException(
                "Failed to complete request",
                ErrorCode::SERVER_ERROR,
                [Constants::PATH => $path]
            );
        }

        return $res;
    }
}
