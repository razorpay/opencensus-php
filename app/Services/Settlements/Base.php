<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use Requests_Response;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use RZP\Http\Request\Requests;
use RZP\Models\Currency\Currency;
use Razorpay\Trace\Logger as Trace;

class Base
{

    //******************* common endpoints for dashboard/api/reminder are listed here ************************//

    const TRANSACTION_HOLD                  = '/twirp/rzp.settlements.transaction.v1.TransactionService/Hold';
    const TRANSACTION_RELEASE               = '/twirp/rzp.settlements.transaction.v1.TransactionService/Release';

    const MERCHANT_CONFIG_GET               = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Get';
    const MERCHANT_CONFIG_CREATE            = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Create';
    const MERCHANT_CONFIG_UPDATE            = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Update';
    const MERCHANT_CONFIG_BULK_UPDATE       = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/BulkUpdate';

    const BANK_ACCOUNT_CREATE               = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Create';

    const LEDGER_RECON_ACTIVE_MTU_CHECK     = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/CheckActiveMtu';
    const LEDGER_RECON_ACTIVE_MTU_ADD       = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/Create';
    const LEDGER_RECON_ACTIVE_MTU_UPDATE    = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/Update';
    const LEDGER_CRON_RESULT_ADD            = '/twirp/rzp.settlements.ledger_cron_result.v1.LedgerCronResultService/Create';
    const LEDGER_CRON_EXECUTION_ADD         = '/twirp/rzp.settlements.ledger_cron_execution.v1.LedgerCronExecutionService/Create';
    const LEDGER_CRON_EXECUTION_UPDATE      = '/twirp/rzp.settlements.ledger_cron_execution.v1.LedgerCronExecutionService/Update';

    const OPTIMIZER_EXTERNAL_SETTLEMENTS_EXECUTION = '/twirp/rzp.settlements.optimizer_settlements.v1.ExecuteOptimizerSettlementsAPI/Execute';


    protected $trace;

    protected $config;

    protected $baseUrl;

    protected $headers;

    protected $auth;

    protected $request;

    const KEY                   = 'key';
    const SECRET                = 'secret';

    const BODY                  = 'body';
    const CODE                  = 'code';

    // Headers
    const ACCEPT                = 'Accept';
    const ADMIN_EMAIL           = 'admin_email';
    const CONTENT_TYPE          = 'Content-Type';
    const X_REQUEST_ID          = 'X-Request-ID';
    const REQUEST_TIMEOUT       = 60;

    const SERVICE_DASHBOARD     = 'dashboard';
    const SERVICE_REMINDER      = 'reminder';
    const SERVICE_PAYOUT        = 'payout';
    const SERVICE_API           = 'api';

    /**
     * Settlements Base constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->trace   = $app['trace'];

        $this->config  = $app['config']->get('applications.settlements_service');

        $this->baseUrl = $this->config['url'];

        $this->auth    = $app['basicauth'];

        $this->request = $app['request'];

        $this->setHeaders();
    }

    /**
     * @param string $endpoint
     * @param array  $data
     * @param string $service
     * @param string $mode
     * @return array
     * @throws Exception\RuntimeException
     * @throws \Throwable
     */
    public function makeRequest(string $endpoint, array $data, string $service, string $mode = null): array
    {
        if ($mode === null)
        {
            $mode = app('rzp.mode') ? app('rzp.mode') : Mode::LIVE;
        }

        $url = $this->baseUrl[$mode] . $endpoint;

        $auth = $this->getAuth($service, $mode);

        $options = [
            'timeout' => self::REQUEST_TIMEOUT,
            'auth'    => $auth,
        ];

        $request = [
            'url'       => $url,
            'method'    => Requests::POST,
            'headers'   => $this->headers,
            'options'   => $options,
            // here we will be making call to proto endpoints which are POST, we can route a GET request
            // from API as POST request to settlement service with empty body i.e {}, for requests which
            // do not need a request body but have to be made POST because of protobuf.
            'content'   => (empty($data) === false) ? json_encode($data) : json_encode(new \stdClass())
        ];

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
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENTS_REQUEST_EXCEPTION,
                [
                    'message'      => $e->getMessage(),
                    'request_body' => $request['content'],
                ]);

            throw $e;
        }

        $this->trace->info(TraceCode::SETTLEMENTS_RESPONSE, [
            'response' => $response->body
        ]);

        $resp = $this->parseResponse($response);

        $this->handleResponseCodes($resp);

        return $resp['body'];
    }

    /**
     * Method to parse response from Settlements.
     *
     * @param  $response
     * @return array
     */
    protected function parseResponse(Requests_Response $response): array
    {
        $code = null;

        $body = null;

        if($response !== null)
        {
            $code = $response->status_code;
            $body = json_decode($response->body, true);
        }

        return [
            'body' => $body,
            'code' => $code,
        ];
    }

    /**
     * @param array $request
     */
    protected function traceRequest(array $request)
    {
        unset($request['options']['auth']);

        $this->trace->info(TraceCode::SETTLEMENTS_REQUEST, $request);
    }

    /**
     * Method to set auth in the request
     *
     * @param string $service
     * @param string $mode
     * @return array
     */
    protected function getAuth(string $service, string $mode) : array
    {
        $service = $this->config[$service][$mode];

        return [
            $service[self::KEY],
            $service[self::SECRET],
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
        $headers[self::X_REQUEST_ID]  = $this->request->getId();

        $this->headers = $headers;
    }

    /**
     * Method to set user headers in the request
     */
    protected function setAdminHeader()
    {
        $this->headers[RequestHeader::X_USER_EMAIL] = $this->getAdminEmail();
    }

    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()[self::ADMIN_EMAIL] ?? '';
    }

    /**
     * @param array  $response
     * @throws Exception\RuntimeException
     * @throws Exception\TwirpException
     * @throws \Throwable
     */
    protected function handleResponseCodes(array $response)
    {
        $code = $response[self::CODE];
        $body = $response[self::BODY];

        if (in_array($code, [200, 400, 401, 500], true) === false)
        {
            throw new Exception\RuntimeException(
                'Unexpected response code received from Settlements.',
                [
                    'status_code'   => $code,
                    'response_body' => $body,
                ]);
        }

        if ($code !== 200)
        {
            $this->trace->warn(TraceCode::SETTLEMENTS_REQUEST_EXCEPTION, [
                'status_code'   => $code,
                'response_body' => $body,
            ]);

            throw new Exception\TwirpException($body);
        }
    }

    /**
     * this method returns the bank account request
     * @param $ba
     * @param $via
     * @return array
     */
    public function getBankAccountCreateRequestForSettlementService($ba, $via = 'payout')
    {
        return [
            'merchant_id'         => $ba->getMerchantId(),
            'account_number'      => $ba->getAccountNumber(),
            'account_type'        => $ba->getAccountType() !== null ? $ba->getAccountType() : 'current',
            'ifsc_code'           => $ba->getIfscCode(),
            'beneficiary_name'    => trim($ba->getBeneficiaryName()),
            'beneficiary_address' => $ba->getBeneficiaryAddress1() ?? '',
            'beneficiary_city'    => $ba->getBeneficiaryCity() ?? '',
            'beneficiary_state'   => $ba->getBeneficiaryState() ?? '',
            'beneficiary_country' => $ba->getBeneficiaryCountry() ?? '',
            'beneficiary_email'   => $ba->getBeneficiaryEmail() ?? '',
            'beneficiary_mobile'  => $ba->getBeneficiaryMobile() ?? '',
            'accepted_currency'   => Currency::INR,
            'extra_info'          => [
                'via' => $via
            ],
        ];
    }
}
