<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

class Razorflow
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    protected $slackSigningSecret;

    const SlackCommandBaseURL = 'rzp.razorflow.slack_command.v1.SlackCommandService';

    const RESPONSE_SUCCESS_CODES = [200];

    const URLS = [
        'invoke_slash_command' => 'InvokeSlashCommand',
    ];

    // Sensitive fields which are not to be logged
    const SENSITIVE_FIELDS = ['response_url'];

    // Headers
    const ACCEPT        = 'Accept';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const REQUEST_TIMEOUT = 30;

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';

    // Slack constants
    const SLACK_REQUEST_TIMESTAMP = 'slack_request_timestamp';
    const SLACK_SIGNATURE         = 'slack_signature';

    const PAYLOAD                 = 'payload';
    const ENDPOINT_TICKET_SUBMIT  = 'ticketSubmit';
    const PAYLOAD_STRING          = 'payload_string';
    /**
     * Razorflow constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.razorflow');

        $this->baseUrl = $this->config['url'];

        $this->request = $app['request'];

        $this->key = $this->config['razorflow_key'];

        $this->secret = $this->config['razorflow_secret'];

        $this->slackSigningSecret = $this->config['razorflow_slack_signing_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param array $inputHeaders
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    public function postSlashCommand(array $input, array $inputHeaders, string $customEndpoint = null, bool $throwExceptionOnFailure = false): array
    {
        $traceInput = $input;

        $this->removeSensitiveData($traceInput);

        $this->trace->info(
            TraceCode::RAZORFLOW_SLACK_REQUEST,
            [
                'headers' => $inputHeaders
            ]
        );

        if ($this->isVerifiedSlackRequest($input, $inputHeaders) === false)
        {
            $this->trace->info(
                TraceCode::RAZORFLOW_SLACK_FAILURE,
                [
                    'headers'  => $inputHeaders,
                    'response' => 'Invalid request',
                ]
            );

            return [
                self::RESPONSE_BODY => 'Invalid request',
                self::RESPONSE_CODE => 400
            ];
        }

        $input = array_merge($input, $inputHeaders);

        $input['custom_endpoint'] = $customEndpoint;

        $response = $this->invokeSlashCommand($input);

        // need to return empty response to allow dialog to close
        if ($customEndpoint === self::ENDPOINT_TICKET_SUBMIT) {
            return [
                self::RESPONSE_BODY =>json_decode("{}"),
                self::RESPONSE_CODE => $response[self::RESPONSE_BODY][self::RESPONSE_CODE] ?? 400
            ];
        }

        return [
            self::RESPONSE_BODY => $response[self::RESPONSE_BODY][self::RESPONSE_BODY] ?? '',
            self::RESPONSE_CODE => $response[self::RESPONSE_BODY][self::RESPONSE_CODE] ?? 400
        ];
    }

    /**
     * @param array $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    public function invokeSlashCommand(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(
            self::SlackCommandBaseURL . '/' . self::URLS['invoke_slash_command'],
            Requests::POST,
            $input,
            $throwExceptionOnFailure
        );
    }

    protected function isVerifiedSlackRequest(array $input, array $inputHeaders) : bool
    {
        $slacktimeStamp = $inputHeaders[self::SLACK_REQUEST_TIMESTAMP] ?? null;

        $slackSignature = $inputHeaders[self::SLACK_SIGNATURE] ?? null;

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        if ((empty($slacktimeStamp) === true) or
            (empty($slackSignature) === true) or
            (abs($currentTimestamp - $slacktimeStamp) > 300))
        {
            // Either an invalid request - or a replay attack
            return false;
        }

        $signatureBaseString = 'v0:' . $slacktimeStamp . ':' . http_build_query($input);

        $mySignature = 'v0=' . hash_hmac('sha256', $signatureBaseString, $this->slackSigningSecret);

        return hash_equals($mySignature, $slackSignature);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \WpOrg\Requests\Exception
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $data);

        $response = $this->sendRazorflowRequest($request);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::RAZORFLOW_RESPONSE, $decodedResponse ?? []);

        return $this->parseResponse($response, $throwExceptionOnFailure);
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]        = 'application/json';
        $headers[self::CONTENT_TYPE]  = 'application/json';
        $headers[self::X_REQUEST_ID]  = $this->request->getId();

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws \WpOrg\Requests\Exception
     */
    protected function sendRazorflowRequest(array $request)
    {
        $this->traceRequest($request);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }
            // TODO: Check why are we catching this and rethrowing
        catch(\WpOrg\Requests\Exception $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::RAZORFLOW_FAILURE_EXCEPTION,
                [
                    'data' => $e->getMessage(),
                ]);

            throw $e;
        }

        return $response;
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        array_unset_recursive($request, 'response_url');

        $this->trace->info(TraceCode::RAZORFLOW_REQUEST, $request);
    }

    /**
     * @param \WpOrg\Requests\Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse($response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204, 302], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from Razorflow service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        $body = json_decode($response->body, true);

        if (isset($body['body']) && isJson($body['body']) === true)
        {
            $body['body'] = json_decode($body['body'], true);
        }

        return [
            self::RESPONSE_BODY => $body,
            self::RESPONSE_CODE => $code,
        ];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        //payload if encoded as string need to be decoded before pushing to razorflow, to allow proto to match
        if ((isset($data[self::PAYLOAD]) === true) && (is_string($data[self::PAYLOAD]) === true))
        {
            $data[self::PAYLOAD_STRING] = $data[self::PAYLOAD];
            $data[self::PAYLOAD]= json_decode($data[self::PAYLOAD], true);
        }

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * @param array $input
     */
    protected function removeSensitiveData(array &$input)
    {
        foreach(self::SENSITIVE_FIELDS as $field)
        {
            unset($input[$field]);
        }
    }
}
