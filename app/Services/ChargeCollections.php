<?php

namespace RZP\Services;

use App;
use Request;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\Org;
use RZP\Exception\BadRequestException;
use RZP\Exception\ServerErrorException;
use RZP\Http\RequestHeader;
use RZP\Models\Pricing\Calculator\Metrics;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payout\Entity;
use RZP\Models\BankingAccount;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\Payout\Constants as PayoutConstants;
use RZP\Models\PayoutsStatusDetails as PayoutsStatusDetails;

class ChargeCollections
{
    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $mode;

    protected $headers;

    protected $auth;

    protected $merchantId;

    /**
     * @var string
     */
    protected $requestTimeout;

    // Headers
    const ACCEPT            = 'Accept';
    const CONTENT_TYPE      = 'Content-Type';
    const X_TASK_ID         = 'X-Task-Id';
    const X_PASSPORT_JWT_V1 = 'X-Passport-JWT-V1';
    const TENANT            = 'tenant';
    const X_DASHBOARD_USER_ID = 'X-Dashboard-User-id';

    const DEFAULT_REQUEST_TIMEOUT   = 60;

    // Charge Collections APIs
    const GetReceiptForInvoiceURL = 'v1/subscription/getReceiptForInvoice';
    const OrgPricingURL = 'v1/org_pricing';
    const FetchOrgPricingURL = 'v1/org_pricing/fetch_multiple';
    const FetchOrgPricingAccessControl = 'v1/org_pricing_access_control/fetch_multiple';
    const ApproveOrgPricing = 'v1/approve_org_pricing';
    const CreateOrgPricingAccessControl = 'v1/org_pricing_access_control';
    const RevokeOrgPricingAccessControl = 'v1/org_pricing_access_control/revoke';
    const RevokeAllOrgPricingAccessControl = 'v1/org_pricing_access_control/revoke_all';
    static $sensitiveFieldsForOrgPricing = ['category', 'sub_category', 'mcc', 'payment_method', 'method_type', 'payment_network', 'issuer_bank',
                                            'payment_feature', 'amount_range_min', 'amount_range_max', 'international', 'fixed_rate', 'percent_rate',
                                            'workflow_id', 'admin_id', 'active'];


    const CHARGE_PAYOUT_STATUS = 'v1/charge_payout_status';

    // Requests/responses will be logged by default or if value for path mentioned here is true.
    const REQUEST_LOGGER_MAP = [];

    const RESPONSE_LOGGER_MAP = [];

    /**
     * Charge Collections constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        $this->config = $app['config']->get('applications.charge_collections');

        $this->mode = (isset($app['rzp.mode']) === true) ? $app['rzp.mode'] : Mode::LIVE;

        $this->baseUrl = $this->config['base_url'][$this->mode];

        $this->key = $this->config['charge_collections_username'][$this->mode];

        $this->secret = $this->config['charge_collections_password'][$this->mode];

        $this->requestTimeout = $this->config['request_timeout'];

        $this->auth = app('basicauth');
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @param int $timeout
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    public function sendRequest(
        string $endpoint,
        string $method,
        array $data = [],
        array $headers = [])
    {
        $request = $this->generateRequest($endpoint, $method, $data, $headers);

        return $this->sendChargeCollectionsRequest($request, $endpoint);
    }

    public function shouldLogResponse(string $endpoint, string $method) :bool
    {
        $mapKey = $method.'_'.$endpoint;
        $logResponse = true;
        if(isset(self::RESPONSE_LOGGER_MAP[$mapKey]))
        {
            $logResponse = self::RESPONSE_LOGGER_MAP[$mapKey];
        }
        return $logResponse;
    }

    public function pushPayoutStatusUpdate(Entity $payout, string $mode)
    {
        $dataToSend = [
            'id'                    => $payout->getId(),
            'charge_id'             => $payout->getNotes()[PayoutConstants::CHARGE_ID],
            'amount'                => $payout->getAmount(),
            'currency'              => $payout->getCurrency(),
            'notes'                 => $payout->getNotes()->toArray(),
            'status'                => $payout->getStatus(),
            'purpose'               => $payout->getPurpose(),
            'mode'                  => $payout->getMode(),
            'failure_reason'        => $payout->getFailureReason(),
            'created_at'            => $payout->getCreatedAt(),
            'status_details'        => $this->getStatusDetailsFromPayout($payout),
            'banking_account_id'    => (new BankingAccount\Service())->fetchBankingAccountIdByBalanceId($payout->balance->getId()),
        ];

        $basDetails = (new BAS\Core)->getBasDetails($payout->balance->getAccountNumber(), $payout->getChannel());

        if (!empty($basDetails)) {
            $dataToSend['banking_account_stmt_detail_id'] = $basDetails->getPublicId();
        }

        $this->sendStatusUpdate($dataToSend, $mode);
    }

    public function sendStatusUpdate(array $input, string $mode) : array
    {
        return $this->sendRequest(self::CHARGE_PAYOUT_STATUS, Requests::POST, $input);
    }

    public function getStatusDetailsFromPayout(PayoutEntity $payout)
    {
        if ($payout->getStatusDetailsId() === null)
        {
            $statusDetailsArray =
                [
                    'reason'        => null,
                    'description'   => null,
                    'source'        => null,
                ];
        }
        else
        {
            $statusDetails = (new PayoutsStatusDetails\Repository())->fetchStatusDetailsFromStatusDetailsId($payout->getStatusDetailsId());

            if ($statusDetails !== null)
            {
                $source = $payout->getSourceForStatusDetails($statusDetails);
            }
            else
            {
                $source = null;
            }

            $statusDetailsArray =
                [
                    'reason'        => $statusDetails['reason'],
                    'description'   => $statusDetails['description'],
                    'source'        => $source,
                ];
        }

        return $statusDetailsArray;
    }

    /**
     * Function used to set headers for the request
     */
    protected function setHeaders(array $headers)
    {
        $this->headers[self::ACCEPT]        = 'application/json';
        $this->headers[self::CONTENT_TYPE]  = 'application/json';
        $this->headers[self::X_TASK_ID]     = $this->app['request']->getTaskId();
        $this->headers[self::X_PASSPORT_JWT_V1] = $this->auth->getPassportJwt($this->baseUrl);
        $this->headers['X-User-Id'] = $this->merchantId;

        if(isset($headers[self::TENANT]) === true)
        {
            if(is_array($headers[self::TENANT]) === true)
            {
                $this->headers[self::TENANT] = $headers[self::TENANT][0];
            }
            else
            {
                $this->headers[self::TENANT] = $headers[self::TENANT];
            }
        }

        if (isset($headers[self::X_DASHBOARD_USER_ID]) === true)
        {
            $this->headers[self::X_DASHBOARD_USER_ID] = $headers[self::X_DASHBOARD_USER_ID];
         }

        // Adds rzp-context-dev-serve header
        $this->headers[RequestHeader::DEV_SERVE_USER] = Request::header(RequestHeader::DEV_SERVE_USER);
    }

    /**
     * @param array $request
     * @param string $endpoint
     * @return array
     * @throws BadRequestException
     * @throws ServerErrorException
     * @throws \Throwable
     */
    protected function sendChargeCollectionsRequest(array $request, string $endpoint)
    {
        $this->traceRequest($request, $endpoint);

        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            $parsedResponse = $this->parseAndReturnResponse($response);

            $logResponse = $this->shouldLogResponse($endpoint, $request['method']);
            if($logResponse === true)
            {
                $this->trace->info(TraceCode::CHARGE_COLLECTIONS_RESPONSE,
                    [
                        'response' => $this->maskSensitiveFields($parsedResponse ?? [], self::$sensitiveFieldsForOrgPricing),
                        'statusCode' => $response->status_code
                    ]);
            }

            if ($response->status_code === 400)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR, null, $parsedResponse);
            }

            else if ($response->status_code === 401)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED, null, $parsedResponse);
            }

            else if ($response->status_code >= 402)
            {
                throw new ServerErrorException(
                    TraceCode::CHARGE_COLLECTIONS_REQUEST_FAILURE,
                    ErrorCode::SERVER_ERROR,
                    $this->maskSensitiveFields($parsedResponse, self::$sensitiveFieldsForOrgPricing)
                );
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->count(Metrics::CHARGE_COLLECTIONS_REQUEST_FAILURE);
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::CHARGE_COLLECTIONS_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);

            throw $e;
        }

        return $parsedResponse;
    }

    protected function parseAndReturnResponse($response)
    {
        $responseArray = json_decode($response->body, true);

        return $responseArray ?? [];
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request, string $endpoint)
    {
        $logRequest = $this->shouldLogRequest($endpoint, $request['method']);

        if($logRequest === true)
        {
            $traceRequest = $request;

            unset($traceRequest['options']['auth']);

            unset($traceRequest['headers'][self::X_PASSPORT_JWT_V1]);

            $traceRequest = $this->maskSensitiveFields($traceRequest, self::$sensitiveFieldsForOrgPricing);

            $this->trace->info(TraceCode::CHARGE_COLLECTIONS_REQUEST, $traceRequest);
        }
    }

    protected function maskSensitiveFields($data, array $sensitiveFields)
    {
        if (!is_array($data) and !is_object($data))
        {
            return $data;
        }

        foreach ($data as $key => $value)
        {
            // if the field is an array or object, recursively call the function to mask its elements
            if (is_array($value) or is_object($value))
            {
                $data[$key] = $this->maskSensitiveFields($value, $sensitiveFields);
            }
            else if (in_array($key, $sensitiveFields) and $value !== null) // mask only sensitive fields with non-null value
            {
                $data[$key] = $this->maskField($key, $value);
            }
        }

        return $data;
    }

    protected function maskField($key, $value)
    {
        if (is_int($value)) // replace with 0 for integer
        {
            return 0;
        }
        else if (is_bool($value)) // replace with false for boolean
        {
            return false;
        }
        else if (is_string($value)) // replace with 'x' for string
        {
            return str_repeat('x', strlen($value));
        }
        else
        {
            return $value;
        }
    }

    public function shouldLogRequest(string $endpoint, string $method) :bool
    {
        $logRequest = true;

        $mapKey = $method.'_'.$endpoint;

        if(isset(self::REQUEST_LOGGER_MAP[$mapKey]))
        {
            $logRequest = self::REQUEST_LOGGER_MAP[$mapKey];
        }

        return $logRequest;
    }

    /**
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    protected function generateRequest(string $endpoint, string $method, array $data, array $headers): array
    {
        $url = $this->baseUrl . $endpoint;

        // json encode if data is must, else ignore
        if (in_array($method, [Requests::POST, Requests::PATCH, Requests::PUT], true) === true)
        {
            $data = (empty($data) === false) ? json_encode($data) : null;
        }

        $options = [
            'timeout' => $this->requestTimeout,
            'auth'    => [
                $this->key,
                $this->secret
            ],
        ];

        $this->setHeaders($headers);

        return [
            'url'       => $url,
            'method'    => $method,
            'headers'   => $this->headers,
            'options'   => $options,
            'content'   => $data
        ];
    }

    /**
     * Get invoice line itmes from charge collections
     * @throws \Exception|\Throwable
     *
     *  $input = [
            'month' => 11,
            'year'  => 2023,
            'merchantId' => "sampleMerchant"
        ];
     */

    public function getReceiptForInvoice(array $input, $requestHeaders = [])
    {
        $this->merchantId = $input['merchantId'];
        return $this->sendRequest(self::GetReceiptForInvoiceURL, Requests::POST, $input, $requestHeaders);
    }

}
