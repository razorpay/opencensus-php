<?php

namespace RZP\Services;

use Requests;
use RZP\Exception;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Refund\Entity as RefundEntity;

class Scrooge
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $mode;

    protected $key;

    protected $secret;

    protected $proxy;

    protected $request;

    protected $headers;

    protected $auth;

    const RefundBaseURL = 'refund';
    const RefundsBaseURL = 'refunds';
    const ListBaseURL = 'list';

    const URLS = [
        'retry'                         => 'retry',
        'get_reports'                   => 'reports',
        'bulk_status_update'            => 'bulk-status-update',
        'get_refunds'                   => 'refunds',
        'status_update'                 => 'status-update',
        'download_refunds'              => 'refunds/download',
        'enqueue'                       => 'enqueue',
        'download_refunds_gateway_file' => 'refunds/download-gateway-file',
    ];

    // Headers
    const ACCEPT        = 'Accept';
    const X_MODE        = 'X-Mode';
    const ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const REQUEST_TIMEOUT = 60;

    /**
     * Scrooge constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.scrooge');

        $this->baseUrl = $this->config['url'];

        // Refer: https://github.com/razorpay/api/issues/6385
        $this->mode = $app['rzp.mode'];

        $this->request = $app['request'];

        $this->key = $this->config['scrooge_key'];

        $this->secret = $this->config['scrooge_secret'];

        $this->auth = $app['basicauth'];

        $this->setHeaders();
    }

    /**
     * @param array $input
     * @param bool  $throwExceptionOnFailure
     *
     * @return array
     */
    public function initiateRefund(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundBaseURL, Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param      $input
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     */
    public function initiateRefundRetry($input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundBaseURL . '/' . $input['id'] . '/' . self::URLS['retry'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getReports(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['get_reports'], Requests::POST, $input);
    }

    /**
     * @param string $id
     * @param array $input
     * @return array
     */
    public function updateRefundStatus(string $id, array $input): array
    {
        return $this->sendRequest(self::RefundBaseURL . '/' . $id . '/' . self::URLS['status_update'],
            Requests::POST, $input);
    }

    /**
     * @param array $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function bulkUpdateRefundStatus(array $input,  bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['bulk_status_update'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function enqueueRefunds(array $input,  bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['enqueue'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getRefunds(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['get_refunds'], Requests::POST, $input);
    }

    public function downloadRefunds(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['download_refunds'], Requests::POST, $input);
    }

    public function downloadGatewayRefundsFile(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['download_refunds_gateway_file'],
            Requests::POST, $input);
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getRefund(string $id): array
    {
        return $this->sendRequest(self::RefundBaseURL . '/' . $id, Requests::GET);
    }

    /**
     * @param string $id
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getPublicRefund(string $id): array
    {
        $id = RefundEntity::verifyIdAndStripSign($id);

        return $this->sendRequest(self::RefundBaseURL . '/' . $id, Requests::GET, ['type' => 'public']);
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     * @param bool   $throwExceptionOnFailure
     *
     * @return array
     */
    protected function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        bool $throwExceptionOnFailure = false): array
    {
        $request = $this->generateRequest($endpoint, $method, $data);

        $response = $this->sendScroogeRequest($request);

        $this->trace->info(TraceCode::SCROOGE_RESPONSE, [
            'response' => $response->body
        ]);

        $decodedResponse = json_decode($response->body, true);

        $this->trace->info(TraceCode::SCROOGE_RESPONSE, $decodedResponse ?? []);

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
        $headers[self::X_MODE]        = $this->mode;
        $headers[self::ADMIN_EMAIL]   = $this->getAdminEmail();
        $headers[self::X_REQUEST_ID]  = $this->request->getId();

        $this->headers = $headers;
    }

    /**
     * @param array $request
     *
     * @return \Requests_Response
     * @throws \Requests_Exception
     */
    protected function sendScroogeRequest(array $request): \Requests_Response
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
        catch(\Requests_Exception $e)
        {
            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::REFUND_SCROOGE_FAILURE_EXCEPTION,
                                [
                                    'data' => $e->getMessage()
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

        $this->trace->info(TraceCode::SCROOGE_REQUEST, $request);
    }

    /**
     * @param \Requests_Response $response
     * @param bool               $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse(\Requests_Response $response, bool $throwExceptionOnFailure = false): array
    {
        $code = $response->status_code;

        if (($throwExceptionOnFailure === true) and
            (in_array($code, [200, 201, 204], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from Scrooge service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body),
            'code' => $code,
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
     * @return string
     */
    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()['admin_email'] ?? '';
    }
}
