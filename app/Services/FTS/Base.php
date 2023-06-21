<?php

namespace RZP\Services\FTS;

use RZP\Models\FundAccount\Validation\Metric;
use \WpOrg\Requests\Response;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\FundTransfer\Redaction;
use RZP\Trace\TraceCode;
use RZP\Base\RepositoryManager;

class Base
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

    protected $mode;

    protected $razorx;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    protected $requestTimeout = 60;

    // Account related URIs
    const FUND_ACCOUNT_CREATE_URI  = '/account';
    const FUND_ACCOUNT_REGISTER_URI  = '/account/register';
    const FUND_TRANSFER_PUBLISH_URI = '/transfer/1/publish';

    // Transfer related URIs
    const FUND_TRANSFER_CREATE_URI = '/transfer';
    const FAIL_QUEUED_TRANSFER_URI = '/transfer/fail';
    const FAIL_QUEUED_TRANSFER_URI_BULK = '/transfers/fail';

    // Source Account related URIs
    const SOURCE_ACCOUNT_CREATE_URI = '/source_account';

    const DIRECT_SOURCE_ACCOUNTS_CREATION = '/direct_source_accounts' ;

    const SOURCE_ACCOUNT_COPY = '/source_account/copy';

    const SOURCE_ACCOUNT_DELETE_URI = '/source_account';

    const BULK_SOURCE_ACCOUNT_UPDATE_URI = '/source_accounts/update';

    const FUND_ACCOUNT_FETCH_URI  = '/account';

    const FUND_TRANSFER_FETCH_URI = '/transfer';

    const FUND_ACCOUNT_STATUS_FETCH_URI  = '/account/status';

    const FUND_TRANSFER_STATUS_FETCH_URI = '/transfer/status';

    const FUND_TRANSFER_ATTEMPTS_UPDATE_URI = '/attempts/update';

    const FUND_TRANSFER_ATTEMPTS_FETCH_STATUS = '/transfers/status';

    const FUND_TRANSFER_ATTEMPTS_CHECK_STATUS = '/transfers/check';

    const FUND_TRANSFER_ATTEMPTS_RAW_BANK_STATUS = '/attempts/verify';

    const FUND_TRANSFER_GET_PENDING_TRANSFERS = '/transfers/pending';

    const FUND_TRANSFER_ATTEMPTS_STATUS_FETCH = '/transfers/status';

    const SOURCE_ACCOUNT = '/source_account';

    const SEND_LOW_BALANCE_ALERT = '/source_account/balance';

    const FETCH_ACCOUNT_BALANCE = '/source_account/fetch_balance';

    const SOURCE_ACCOUNT_MAPPING = '/source_account_mappings';

    const DIRECT_ACCOUNT_ROUTING_RULES = '/direct_account_routing_rules';

    const PREFERRED_ROUTING_WEIGHT = '/preferred_routing_weights';

    const ACCOUNT_TYPE_MAPPING = '/account_type_mappings';

    const SCHEDULE = '/channel_health_events/schedules';

    const MANUAL_OVERRIDE = '/channel_health_events/manual_override';

    const SCHEDULE_GET_ROUTE = '/routing/schedules';

    const TRIGGER_STATUS_LOG_GET_ROUTE = '/routing/trigger_status_logs';

    const CHANNEL_INFORMATION_STATUS_LOG_GET_ROUTE = '/routing/channel_information_status_logs';

    const FUND_TRANSFER_ATTEMPTS_INITIATE_URI = '/attempts/process';

    const FTS_ONE_OFF_DB_MIGRATE_URL = '/one_off_db_migrate';

    const FTS_MERCHANT_CONFIGURATIONS_URL = '/merchant_configurations';

    const FTS_HOLIDAY_URL = '/holiday';

    const FTS_FAIL_FAST_STATUS_LOGS_GET_URL = '/routing/fail_fast_status_logs';

    const FTS_NEW_CHANNEL_HEALTH_STATS = '/routing/channel_health_stats';

    const FTS_TRANSFER_RETRY_BULK_URL = '/transfers/retry';

    const FTS_TRIGGER_HEALTH_STATUS    = '/routing/trigger_health_status';

    const FTS_FAIL_FAST_STATUS_MANUAL_UPDATE = '/channel_health_events/fail_fast_status/manual_update';

    const FTS_KEY_VALUE_STORE_LOGS_GET_URL = '/key_value_store/logs';

    const FTS_KEY_VALUE_STORE_PATCH_URL = '/key_value_store';

    const FTS_KEY_VALUE_STORE_POST_URL = '/key_value_store';

    const FTS_OTP_CREATE = '/otp/send';

    const FTS_ROUTING_FETCH_MODE = '/routing/mode_selection';

    // Headers
    const ACCEPT        = 'Accept';
    const ADMIN_EMAIL   = 'admin_email';
    const CONTENT_TYPE  = 'Content-Type';
    const X_REQUEST_ID  = 'X-Request-ID';

    const TRANSFER_RETRY = 'transfer_retry';

    const ALLOWED_FTS_ACTION = [
        self::TRANSFER_RETRY,
    ];

    /**
     * FTS Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->request = $app['request'];

        $this->mode    = $app['rzp.mode'];

        $this->auth    = $app['basicauth'];

        $this->config  = $app['config']->get('applications.fts');

        $this->baseUrl = $this->config[$this->mode]['url'];

        $this->key     = $this->config[$this->mode]['fts_key'];

        $this->secret  = $this->config[$this->mode]['fts_secret'];

        $this->repo = $app['repo'];

        $this->razorx = $app['razorx'];

        $this->setHeaders();
    }

    /**
     * Creates and Sends the Request
     * to FTS endpoint.
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function createAndSendRequest(
        string $endpoint,
        string $method,
        array $data = []): array
    {
        $request = $this->generateRequest($method, $endpoint, $data);

        if ($this->mode === Mode::TEST)
        {
            $response = $this->getMockResponseByEndpoint($endpoint);
        }
        else
        {
            $this->trace->info(TraceCode::FTS_REQUEST, [
                'url'       => $this->baseUrl . $endpoint,
                'method'    => $method,
                'headers'   => $this->headers,
                'content'   => (new Redaction())->redactData($data)
            ]);
            $response = $this->sendFtsRequest($request);
        }

        $this->trace->info(TraceCode::FTS_RESPONSE, [
            'response' => (new Redaction())->redactData(json_decode($response->body, true))
        ]);

        if ($response->status_code === 409)
        {
            throw new Exception\RecordAlreadyExists(
                'record already exists',
                ErrorCode::BAD_REQUEST_FTS_DUPLICATE_TRANSFER_REQUEST_SENT
            );
        }

        return $this->parseResponse($response);
    }

    /**
     * Generates request using the given params
     * Issue with DELETE method: https://github.com/rmccue/Requests/issues/91
     * Fix for DELETE method: https://github.com/rmccue/Requests/pull/188
     *
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $method, string $endpoint, array $data): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore.
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT, Requests::DELETE], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => $this->requestTimeout,
            'auth'    => [
                $this->key,
                $this->secret,
            ],
        ];

        if ($method === Requests::DELETE)
        {
            $options += [ 'data_format' => 'body' ];
        }

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * Method to set headers in the request
     */
    protected function setHeaders()
    {
        $headers = [];

        $headers[self::ACCEPT]       = 'application/json';
        $headers[self::CONTENT_TYPE] = 'application/json';

        $this->headers = $headers;
    }

    /**
     * Method to send request to FTS endpoint
     *
     * @param array $request
     * @return \WpOrg\Requests\Response
     * @throws \Throwable
     */
    protected function sendFtsRequest(array $request)
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);
        }

        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTS_FAILURE_EXCEPTION,
                [
                    'message'      => $e->getMessage(),
                ]);

            $this->trace->count(Metric::FTS_FAILURE_EXCEPTION_COUNT,
                                [
                                    'route_name'  => app('request.ctx')->getRoute() ?: 'none',
                                    'mode'        => app('rzp.mode') ?: 'none',
                                    'environment' => app('env'),
                                ]);

            throw $e;
        }

        return $response;
    }


    /**
     * Method to parse response from FTS
     *
     * @param  $response
     * @return array
     * @throws Exception\RuntimeException
     */
    protected function parseResponse($response): array
    {
        $code = null;

        if($response !== null)
        {
            $code = $response->status_code;
        }

        if (in_array($code, [200, 201, 204, 400], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from FTS.',
                [
                    'status_code'   => $code,
                ]);
        }

        return [
            'body' => json_decode($response->body, true),
            'code' => $code,
        ];
    }

    /**
     * Checks if a valid action is initiated to FTS
     *
     * @param string $action
     * @return bool
     */
    public static function isValidFtsAction(string $action): bool
    {
        return (in_array($action, self::ALLOWED_FTS_ACTION, true) === true);
    }

    protected function getMockResponseByEndpoint(string $endpoint)
    {
        if ($endpoint === self::FUND_TRANSFER_CREATE_URI)
        {
            return $this->mockCreateFundTransferResponse();
        }
        if ($endpoint === self::DIRECT_ACCOUNT_ROUTING_RULES)
        {
            return $this->mockDirectRoutingRuleGetResponse();
        }
        if ($endpoint === self::BULK_SOURCE_ACCOUNT_UPDATE_URI)
        {
            return $this->mockUpdateSourceAccountResponse();
        }
        if ($endpoint === self::FTS_OTP_CREATE)
        {
            return $this->mockCreateOtpRequestResponse();
        }
    }

    public function mockCreateFundTransferResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->status_code = 201;

        $content = json_encode([
                Constants::STATUS           => Constants::STATUS_CREATED,
                Constants::MESSAGE          => 'fund transfer sent to fts.',
                Constants::FUND_TRANSFER_ID => random_integer(2),
                Constants::FUND_ACCOUNT_ID  => random_integer(2),
        ]);

        $response->body = $content;

        return $response;
    }

    public function mockDirectRoutingRuleGetResponse()
    {
        $response = new \WpOrg\Requests\Response();
        $response->status_code = 200;
        $response -> body= json_encode([
            "direct_account_routing_rules"=> [
                '0' => [
                    "channel"=> "ICICI",
                    "created_at"=> 1624607325,
                    "deleted_at"=> null,
                    "deleted_by"=> null,
                    "id"=> 1,
                    "mode"=> "IMPS",
                    "mozart_identifier"=>"V2",
                    "product"=> "PAYOUT",
                    "source_account_id"=> 1,
                    "updated_at"=> 1624607325,
                    "merchant_id"=>100000000000
                ],
                '1'=>[
                    "channel"=> "YESBANK",
                    "created_at"=> 1624607325,
                    "deleted_at"=> null,
                    "deleted_by"=> null,
                    "id"=> 2,
                    "mode"=> "IMPS",
                    "mozart_identifier"=>"V2",
                    "product"=> "PAYOUT",
                    "source_account_id"=> 1,
                    "updated_at"=> 1624607325,
                    "merchant_id"=>100000000000
                ]
            ]
            ]);

        return $response;
    }

    protected function setAdminHeader()
    {
        $this->headers[RequestHeader::X_USER_EMAIL] = $this->getAdminEmail();
    }

    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()[self::ADMIN_EMAIL] ?? 'EMAIL_NOT_FOUND';
    }

    public function mockUpdateSourceAccountResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->status_code = 200;

        $content = json_encode([
                                   Constants::STATUS  => 'updated',
                                   Constants::MESSAGE => 'source account updated at FTS',
                               ]);

        $response->body = $content;

        return $response;
    }

    public function mockCreateOtpRequestResponse()
    {
        $response = new \WpOrg\Requests\Response();

        $response->status_code = 200;

        $content = json_encode([
            Constants::STATUS  => 'successful',
            Constants::MESSAGE => 'OTP was successfully generated',
        ]);

        $response->body = $content;

        return $response;
    }

    public function setRequestTimeout(int $requestTimeout)
    {
        $this->requestTimeout = $requestTimeout;
    }
}
