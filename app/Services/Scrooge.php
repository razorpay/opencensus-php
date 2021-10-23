<?php

namespace RZP\Services;

use RZP\Http\Request\Requests;
use RZP\Exception;
use RZP\Models\Payout\Entity;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

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
    const MerchantsBaseURL = 'merchants';

    const RESPONSE_SUCCESS_CODES = [200];

    const URLS = [
        'retry'                                => 'retry',
        'get_reports'                          => 'reports',
        'bulk_status_update'                   => 'bulk-status-update',
        'reverse_failed_refunds'               => 'reverse_failed_refunds',
        'bulk_recon'                           => 'bulk-reconcile',
        'bulk_reference1_update'               => 'bulk-reference1-update',
        'get_refunds'                          => 'refunds',
        'get_dashboard_init_data'              => 'init',
        'status_update'                        => 'status-update',
        'verify'                               => 'verify',
        'download_refunds'                     => 'refunds/download',
        'enqueue'                              => 'enqueue',
        'download_refunds_gateway_file'        => 'refunds/download-gateway-file',
        'download_refunds_gateway_report_file' => 'refunds/download-gateway-report-file',
        'instant_refunds_mode'                 => 'instant_refunds_mode',
        'get_file_based_refunds'               => 'file_based_refunds',
        'refresh_fta_modes'                    => 'fta_modes_refresh',
        'fetch_refund_create_data'             => 'fetch/refund_create_data',
        'fetch_instant_refunds_modes'          => 'fetch/instant_refund_mode_configs',
        'instant-refunds-decisioning-helper'   => 'instant-refunds-decisioning-helper',
        'fetch-from-gateway-reference-value'   => 'fetch_from_gateway_reference_value',
        // Retry routes
        'retry_with_verify'                    => 'retry/with_verify',
        'retry_without_verify'                 => 'retry/without_verify',
        'retry_source_fund_transfers'          => 'retry/source_fund_transfers',
        'retry_custom_fund_transfers'          => 'retry/custom_fund_transfers',
        'retry_with_attempt_appended_id'       => 'retry/with_attempt_appended_id',
        'create_new_refund_v2'                 => 'create-new-refund-v2',
        'payouts_status_update'                => 'payouts/status_update',
    ];

    // Headers
    const ACCEPT        = 'Accept';
    const X_MODE        = 'X-Mode';
    const ADMIN_EMAIL   = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const REQUEST_TIMEOUT = 60;

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';
    const RESPONSE_STATUS        = 'status';

    const MODE = 'mode';

    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    const PASSPORT_AUD = 'scrooge';
    const PAYMENT_PAGE = 'Payment-Page';

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
     * @param bool $throwExceptionOnFailure
     * @return array
     */
    public function initiateRefundRecon(array $input, bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['bulk_recon'], 'POST', $input, $throwExceptionOnFailure);
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
     * @param string $id
     * @param array $input
     * @return array
     */
    public function verifyRefund(string $id): array
    {
        return $this->sendRequest(self::RefundBaseURL . '/' . $id . '/' . self::URLS['verify'],
            Requests::POST);
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
     */
    public function reverseFailedRefunds(array $input,  bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['reverse_failed_refunds'],
            Requests::POST, $input, $throwExceptionOnFailure);
    }

    /**
     * @param array $input
     * @param bool $throwExceptionOnFailure
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function bulkUpdateRefundReference1(array $input,  bool $throwExceptionOnFailure = false): array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['bulk_reference1_update'],
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

    /**
     * @param array $input
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getFileBasedRefunds(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['get_file_based_refunds'], Requests::POST, $input, true);
    }

    /**
     * @param array $input
     * @return array
     */
    public function downloadRefunds(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['download_refunds'], Requests::POST, $input);
    }

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function setInstantRefundsMode(array $input, string $merchantId = null): array
    {
        if (empty($merchantId) === false)
        {
            $input[RefundConstants::SCROOGE_MERCHANT_ID] = $merchantId;
        }

        return $this->sendRequest(
            self::MerchantsBaseURL . '/' . self::URLS['instant_refunds_mode'],
            Requests::POST,
            $input
        );
    }

    /**
     * @param string $id
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function expireInstantRefundsModeConfig(string $id, array $input, string $merchantId = null): array
    {
        if (empty($merchantId) === false)
        {
            $input[RefundConstants::SCROOGE_MERCHANT_ID] = $merchantId;
        }

        return $this->sendRequest(
            self::MerchantsBaseURL . '/' . self::URLS['instant_refunds_mode'] . '/' . $id . '/expire',
            Requests::PUT,
            $input
        );
    }

    /**
     * @param array $input
     * @return array
     */
    public function refreshFtaModes(array $input): array
    {
        return $this->sendRequest(
            self::MerchantsBaseURL . '/' . self::URLS['refresh_fta_modes'],
            Requests::POST,
            $input
        );
    }

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function fetchInstantRefundsModeConfigs(array $input, string $merchantId = null): array
    {
        if (empty($merchantId) === false)
        {
            $input[RefundConstants::SCROOGE_MERCHANT_ID] = $merchantId;
        }

        return $this->sendRequest(
            self::MerchantsBaseURL . '/' . self::URLS['fetch_instant_refunds_modes'],
            Requests::POST,
            $input
        );
    }

    /**
     * @param array $input
     * @return array
     */
    public function downloadGatewayRefundsFile(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['download_refunds_gateway_file'],
            Requests::POST, $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function downloadGatewayReportsFile(array $input): array
    {
        return $this->sendRequest(self::ListBaseURL . '/' . self::URLS['download_refunds_gateway_report_file'],
            Requests::POST, $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function dashboardInit(array $input): array
    {
        return $this->sendRequest(
            self::ListBaseURL . '/' . self::URLS['get_dashboard_init_data'],
            Requests::GET,
            $input
        );
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
     * @param array $params
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Requests_Exception
     */
    public function getPublicRefund(string $id, array $params = []): array
    {
        $queryParams = array_merge(['type' => 'public'], $params);

        $id = RefundEntity::verifyIdAndStripSign($id);

        return $this->sendRequest(self::RefundBaseURL . '/' . $id, Requests::GET, $queryParams);
    }

    /**
     * @param array $params
     * @return array
     */
    public function getInstantRefundsMode(string $merchantId, array $params): array
    {
        $scroogeResponse = $this->sendRequest(
            self::MerchantsBaseURL . '/' . $merchantId . '/' . self::URLS['instant_refunds_mode'],
            Requests::GET,
            $params);

        $scroogeResponseCode = $scroogeResponse[self::RESPONSE_CODE];

        if (in_array($scroogeResponseCode, [200, 201, 204], true) === true)
        {
            return $scroogeResponse[self::RESPONSE_BODY];
        }

        return [
            'mode' => null
        ];
    }

    /**
     * @param array $body
     * @return array
     */
    public function fetchRefundCreateData(array $body): array
    {
        $scroogeResponse = $this->sendRequest(
            self::MerchantsBaseURL . '/' . self::URLS['fetch_refund_create_data'],
            Requests::POST,
            $body);

        $scroogeResponseCode = $scroogeResponse[self::RESPONSE_CODE];

        if (in_array($scroogeResponseCode, [200, 201, 204], true) === true)
        {
            return $scroogeResponse[self::RESPONSE_BODY];
        }

        return [
            RefundConstants::GATEWAY_REFUND_SUPPORT => true,
            RefundConstants::INSTANT_REFUND_SUPPORT => false,
            RefundConstants::MODE => null,
            RefundConstants::PAYMENT_AGE_LIMIT_FOR_GATEWAY_REFUND => null,
        ];
    }

    /**
     * @param array $input
     * @return array
     */
    public function getRefundsFromPaymentIdAndGatewayId(array $input)
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['fetch-from-gateway-reference-value'], Requests::POST, $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function retryRefundsWithVerify(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['retry_with_verify'],
            Requests::POST,
            $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function retryRefundsWithoutVerify(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['retry_without_verify'],
            Requests::POST,
            $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function retryRefundsWithAppend(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['retry_with_attempt_appended_id'],
            Requests::POST,
            $input);
    }

    /**
     * @param array $input
     * @return array
     */
    public function retryRefundsViaSourceFundTransfers(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['retry_source_fund_transfers'],
            Requests::POST,
            $input);
    }

    /**
     * Support admin action for bulk retrying refunds via FTA to custom sources
     * @param array $input
     * @return array
     */
    public function retryRefundsViaCustomFundTransfers(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['retry_custom_fund_transfers'],
            Requests::POST,
            $input);
    }

    /**
     * New refund create V2 API.
     * Validate incoming refund requests in sync
     * @param array $input
     * @return array
     */
    public function createNewRefundV2(array $input): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

        if (isset($input['payment_page']) === true)
        {
            $customheader = [
                self::PAYMENT_PAGE => ($input['payment_page'] ? 'yes': 'no'),
            ];
            $this->setCustomHeaders($customheader);
            unset($input['payment_page']);
        }

        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['create_new_refund_v2'],
            Requests::POST,
            $input);
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
     * Function to set custom headers for any request
     */
    protected function setCustomHeaders(array $customHeaders = [])
    {
        $this->headers = $this->headers + $customHeaders;
    }

    /**
     * Function to send passport token in headers in API call to Scrooge
     */
    protected function enablePassport()
    {
        $customHeader = [
            self::X_PASSPORT_JWT_V1 => $this->auth->getPassportJwt(self::PASSPORT_AUD),
        ];

        // set custom headers
        $this->setCustomHeaders($customHeader);
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
            (in_array($code, [200, 201, 204, 302], true) === false))
        {

            throw new Exception\RuntimeException(
                'Unexpected response code received from Scrooge service.',
                [
                    'status_code'   => $code,
                    'response_body' => json_decode($response->body),
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
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

    /**
     * Status update upon receiving webhook from payout
     * @param array $input
     * @param string $mode
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function sendStatusUpdate(array $input, string $mode) : array
    {
        return $this->sendRequest(self::RefundsBaseURL . '/' . self::URLS['payouts_status_update'],
            Requests::POST, $input, true);
    }

    public function pushPayoutStatusUpdate(Entity $payout, string $mode)
    {
        $dataToSend = $this->getDataFromPayout($payout);

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    //TODO: add payload for refunds update
    protected function getDataFromPayout(Entity $payout): array
    {
        return [
            'id'                => $payout->getId(),
            'status'            => $payout->getStatus(),
            'rrn'               => $payout->getUtr(),
            'mode'              => $payout->getMode(),
            'channel'           => $payout->getChannel(),
            'reference_id'      => $payout->getReferenceId(),
            'error_code'        => $payout->getStatusCode(),
            'error_description' => $payout->getFailureReason(),
        ];
    }
}
