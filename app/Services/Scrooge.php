<?php

namespace RZP\Services;

use App;
use RZP\Error\PublicErrorCode;
use RZP\Exception;
use \WpOrg\Requests\Exception as Requests_Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Models\Payout\Entity;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Edge\Passport\Passport;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Payment\Processor\Constants;
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

    protected $route;

    const RefundBaseURL = 'refund';
    const RefundsBaseURL = 'refunds';
    const PaymentsBaseURL = 'payments';
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
        // route use for fetching refund based on payment and refund entity
        'refund_internal_fetch'                => 'internal/fetch',
        // Currently only table `gateway_keys` is supported for bulk entities
        'bulk_gateway_keys'                    => 'entities/gateway_keys',
        'verify_refunds'                       => 'bulk_verify'
    ];

    // Headers
    const ACCEPT            = 'Accept';
    const X_MODE            = 'X-Mode';
    const ADMIN_EMAIL       = 'X-Dashboard-Admin-Email';
    const CONTENT_TYPE      = 'Content-Type';
    const X_REQUEST_ID      = 'X-Request-ID';
    Const X_ADMIN_EMAIL     = 'X-ADMIN-EMAIL';
    Const X_USER_EMAIL      = 'X-USER-EMAIL';
    Const X_IS_CRON         = 'X-IS-CRON';
    Const X_IS_DASHBOARD    = 'X-IS-DASHBOARD';
    Const X_INIT_SOURCE     = 'X-INIT-SOURCE';
    Const ROUTE_NAME        = 'route-name';
    Const IS_BATCH          = 'is-batch';

    const REQUEST_TIMEOUT = 60;

    const RESPONSE_CODE          = 'code';
    const RESPONSE_BODY          = 'body';
    const RESPONSE_STATUS        = 'status';

    const MODE = 'mode';

    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';

    const PASSPORT_AUD = 'scrooge';
    const PAYMENT_PAGE = 'Payment-Page';
    const REFUND_ONLY_UNCAPTURED = 'refund-only-uncaptured';

    const TERMINAL_ID = 'terminal_id';

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

        $this->route = $app['api.route'];

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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
     */
    public function bulkUpdateRefundStatus(array $input,  bool $throwExceptionOnFailure = false): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
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
     * @throws Requests_Exception
     */
    public function getPublicRefund(string $id, array $params = []): array
    {
        $queryParams = array_merge(['type' => 'public'], $params);

        $id = RefundEntity::verifyIdAndStripSign($id);

        return $this->sendRequest(self::RefundBaseURL . '/' . $id, Requests::GET, $queryParams);
    }

    /**
     * @param string $id
     * @param array $params
     * @return string
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function getRefundTerminalId(string $id, array $params = []): string
    {
        $id = RefundEntity::verifyIdAndStripSign($id);

        $scroogeResponse = $this->sendRequest(self::RefundBaseURL . '/' . $id, Requests::GET);

        $scroogeResponseCode = $scroogeResponse[self::RESPONSE_CODE];

        if (in_array($scroogeResponseCode, [200, 201, 204], true) === true)
        {
            $scroogeResponseBody = $scroogeResponse[self::RESPONSE_BODY];

            $scroogeTerminalId =
                (empty($scroogeResponseBody[self::TERMINAL_ID]) === false) ? $scroogeResponseBody[self::TERMINAL_ID] : '';


            return $scroogeTerminalId;
        }

        return '';
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

        $isBatch = (($this->auth->isBatchFlow() === true) or ($this->auth->isBatchApp() === true));

        //TODO check this in case of refund on boarding to edge
        $customheader = [
            RequestHeader::X_Creator_Id         => $this->request->header(RequestHeader::X_Creator_Id) ?? null,
            RequestHeader::X_Creator_Type       => $this->request->header(RequestHeader::X_Creator_Type) ?? null,
            self::X_ADMIN_EMAIL                 => $this->getAdminEmail(),
            self::X_USER_EMAIL                  => $this->getUserEmail(),
            self::X_IS_CRON                     => $this->auth->isCron(),
            self::X_IS_DASHBOARD                => $this->auth->isDashboardApp(),
            self::ROUTE_NAME                    => $this->route->getCurrentRouteName(),
            self::IS_BATCH                      => $isBatch,
        ];

        if (isset($input['payment_page']) === true)
        {
            $customheader[self::PAYMENT_PAGE] = ($input['payment_page'] ? 'yes': 'no');
            unset($input['payment_page']);
        }

        if (isset($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT]) === true)
        {
            $customheader[self::REFUND_ONLY_UNCAPTURED] = ($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT] === true);
            unset($input[RefundConstants::REFUND_AUTHORIZED_PAYMENT]);
        }

        $this->setCustomHeaders($customheader);

        $scroogeResponse = $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['create_new_refund_v2'],
            Requests::POST,
            $input);

        if (in_array($scroogeResponse['code'], [200, 201, "200", "201"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    public function fetchRefundInternal($input)
    {
        $resp = $this->sendRequest(
            self::RefundsBaseURL. '/'. self::URLS['refund_internal_fetch'],
            Requests::POST,
            $input,
            true
        );

        if(empty($resp['body']['data']) == true)
        {
            return new Exception\RuntimeException(
                'Unexpected response code received from Scrooge service.',
                [
                    'input'         => $input,
                    'response_body' => json_decode($resp['body']),
                ]);
        }

        return $resp;
    }

    public function fetchRefunds($input)
    {
        $resp = $this->fetchRefundInternal($input);

        $refunds = new PublicCollection();

        foreach ($resp['body']['data'] as $ref)
        {
            $refund = $this->forceFillRefundFromResponse($ref);

            $refunds->add($refund);
        }

        return $refunds;
    }

    public function fetchRefund($input)
    {
        $resp = $this->fetchRefundInternal($input);

        return $this->forceFillRefundFromResponse($resp['body']['data'][0]);
    }

    private function forceFillRefundFromResponse($response)
    {
        if (empty($response) === false)
        {
            if ((isset($response['notes']) === true) and (is_array($response['notes']) === false))
            {
                $response['notes'] = json_decode($response['notes']);
            }

            // adding this layer since speed on scrooge is mapped to speed requested on API
            if(isset($response['speed']) === true)
            {
                $response['speed_processed'] = $response['speed'];
            }

            return (new RefundEntity())->forceFill($response);
        }
        return null;
    }

    /**
     * @param $id
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function refundsFetchById($id, array $input): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

        $scroogeResponse = $this->sendRequest(
            self::RefundsBaseURL . '/' . $id,
            Requests::GET,
            $input);

        if (in_array($scroogeResponse['code'], [200, "200"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function refundsFetchMultiple(array $input): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

        $scroogeResponse = $this->sendRequest(
            self::RefundsBaseURL,
            Requests::GET,
            $input);

        if (in_array($scroogeResponse['code'], [200, 201, "200", "201"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * @param $paymentId
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function refundsFetchByPayment($paymentId, array $input): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

        $scroogeResponse = $this->sendRequest(
            self::PaymentsBaseURL . '/' . $paymentId . '/' . self::URLS['get_refunds'],
            Requests::GET,
            $input);

        if (in_array($scroogeResponse['code'], [200, 201, "200", "201"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * @param $paymentId
     * @param $refundId
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function refundsFetchByIdAndPayment($paymentId, $refundId): array
    {
        // send passport token to Scrooge
        $this->enablePassport();

        $scroogeResponse = $this->sendRequest(
            self::PaymentsBaseURL . '/' . $paymentId . '/' . self::URLS['get_refunds'] . '/' . $refundId,
            Requests::GET,
            []);

        if (in_array($scroogeResponse['code'], [200, 201, "200", "201"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * Public API. Used to update refund notes
     * @param $refundId
     * @param array $input
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
     */
    public function updateRefund($refundId, array $input)
    {
        // send passport token to Scrooge
        $this->enablePassport();

        $scroogeResponse = $this->sendRequest(
            self::RefundsBaseURL . '/' . $refundId,
            Requests::PATCH,
            $input);

        if (in_array($scroogeResponse['code'], [200, "200"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * @param $entity entity name in scrooge
     * @param $input
     * @return void
     *
     * This is functional only for `gateway_keys` entity for now
     */
    public function fetchBulkGatewayKeys($input)
    {
        $scroogeResponse = $this->sendRequest(
            self::URLS['bulk_gateway_keys'],
            Requests::POST,
            $input,
           true);

        if (in_array($scroogeResponse['code'], [200, "200"]) == false)
        {
            $this->toPublicErrorResponse($scroogeResponse);
        }

        // body has the actual scrooge response
        return $scroogeResponse['body'];
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param bool $throwExceptionOnFailure
     *
     * @return array
     * @throws Exception\RuntimeException
     * @throws Requests_Exception
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
        $headers[self::X_INIT_SOURCE] = $this->auth->getInternalApp();

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
        $passportHeader = (empty($this->auth->getPassportFromJob()) === false) ? $this->auth->getPassportFromJob() : $this->auth->getPassportJwt(self::PASSPORT_AUD);

        $customHeader = [
            self::X_PASSPORT_JWT_V1 => $passportHeader,
        ];

        // set custom headers
        $this->setCustomHeaders($customHeader);
    }

    /**
     * @param array $request
     *
     * @return \WpOrg\Requests\Response
     * @throws Requests_Exception
     */
    protected function sendScroogeRequest(array $request)
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
        catch(Requests_Exception $e)
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

        unset($request['headers'][Passport::PASSPORT_JWT_V1 ]);

        $this->trace->info(TraceCode::SCROOGE_REQUEST, $request);
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

    protected function toPublicErrorResponse(array $response)
    {
        $publicErrorCode = $response['body']['public_error']['code'] ?? ErrorCode::SERVER_ERROR;

        $publicErrorMessage = $response['body']['public_error']['message'] ?? PublicErrorDescription::SERVER_ERROR;

        $internalErrorCode = $response['body']['internal_error']['code'] ?? ErrorCode::SERVER_ERROR;

        // If errorcode is undefined, will fallback to server_error
        if (defined(ErrorCode::class . '::' . $publicErrorCode) === false)
        {
            $publicErrorCode = ErrorCode::SERVER_ERROR;

            $publicErrorMessage = PublicErrorDescription::SERVER_ERROR;
        }

        if (defined(ErrorCode::class . '::' . $internalErrorCode) === false)
        {
            $internalErrorCode = ErrorCode::SERVER_ERROR;
        }

        $exceptionType = str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $publicErrorCode))));

        if ($publicErrorCode != ErrorCode::SERVER_ERROR)
        {
            $exceptionType = str_replace('Error', '', $exceptionType);
        }

        switch ($publicErrorCode)
        {
            case ErrorCode::BAD_REQUEST_ERROR:
                switch ($internalErrorCode)
                {
                    case ErrorCode::BAD_REQUEST_ONLY_INSTANT_REFUND_SUPPORTED:
                    case ErrorCode::BAD_REQUEST_REFUND_NOT_SUPPORTED_BY_THE_BANK:
                        $args = [constant(ErrorCode::class . '::' . $internalErrorCode), null, null, $publicErrorMessage];
                        break;
                    case ErrorCode::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT:
                        $args = [PublicErrorCode::BAD_REQUEST_ERROR, null, null, PublicErrorDescription::BAD_REQUEST_TOTAL_REFUND_AMOUNT_IS_GREATER_THAN_THE_PAYMENT_AMOUNT];
                        break;
                    default:
                        $args = [constant(ErrorCode::class . '::' . $internalErrorCode)];
                        break;
                }
                break;

            case ErrorCode::SERVER_ERROR:
                $args = [$publicErrorMessage, constant(ErrorCode::class . '::' . $internalErrorCode)];
                break;

            default:
                $args = [$publicErrorMessage];
                break;
        }

        $class = 'RZP\Exception' . '\\' . $exceptionType . 'Exception';

        throw new $class(...$args);
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
        $app = App::getFacadeRoot();

        $variant = $app['razorx']->getTreatment(UniqueIdEntity::generateUniqueId(),
            "scrooge_edge_migration",
            $app['rzp.mode'] ?? 'live');

        $url = $this->baseUrl . $endpoint;

        if ($this->baseUrl == "https://scrooge.razorpay.com/v1/" && $variant==='on')
        {
            $url = "https://scrooge-temp.razorpay.com/v1/" . $endpoint;
        }

        $this->trace->info(TraceCode::SCROOGE_EDGE_MIGRATION, [
            'variant'    => $variant,
            'url'        => $url,
        ]);

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
     * @return string
     */
    protected function getUserEmail(): string
    {
        return $this->auth->getDashboardHeaders()[Constants::USER_EMAIL] ?? '';
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

    /**
     * @param array $input
     * @return array
     */
    public function verifyRefunds(array $input): array
    {
        return $this->sendRequest(
            self::RefundsBaseURL . '/' . self::URLS['verify_refunds'],
            Requests::POST,
            $input);
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
