<?php

namespace RZP\Services\Settlements;

use RZP\Exception;
use \WpOrg\Requests\Response;
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
    const ORG_CONFIG_GET                    = '/twirp/rzp.settlements.org_settlement_config.v1.OrgConfigService/Get';
    const MERCHANT_CONFIG_GET               = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Get';
    const MERCHANT_CONFIG_CREATE            = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Create';
    const MERCHANT_CONFIG_UPDATE            = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/Update';
    const MERCHANT_CONFIG_BULK_UPDATE       = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/BulkUpdate';

    const BANK_ACCOUNT_CREATE               = '/twirp/rzp.settlements.bank_account.v1.BankAccountService/Create';
    const ORG_BANK_ACCOUNT_CREATE           = '/twirp/rzp.settlements.org_bank_account.v1.OrgBankAccountService/Create';
    const ORG_BANK_ACCOUNT_UPDATE           = '/twirp/rzp.settlements.org_bank_account.v1.OrgBankAccountService/Update';
    const PROCESS_CUSTOM_SETTLEMENTS_FILE   = '/twirp/rzp.settlements.org_settlements.v1.OrgSettlementsService/UpdateSettlementStatus';
    const GET_ORG_SETTLEMENT                = '/twirp/rzp.settlements.org_settlements.v1.OrgSettlementsService/Get';

    const LEDGER_RECON_ACTIVE_MTU_CHECK     = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/CheckActiveMtu';
    const LEDGER_RECON_ACTIVE_MTU_ADD       = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/Create';
    const LEDGER_RECON_ACTIVE_MTU_UPDATE    = '/twirp/rzp.settlements.ledger_recon_mtu.v1.LedgerReconMtuService/Update';
    const LEDGER_CRON_RESULT_ADD            = '/twirp/rzp.settlements.ledger_cron_result.v1.LedgerCronResultService/Create';
    const LEDGER_CRON_EXECUTION_ADD         = '/twirp/rzp.settlements.ledger_cron_execution.v1.LedgerCronExecutionService/Create';
    const LEDGER_CRON_EXECUTION_UPDATE      = '/twirp/rzp.settlements.ledger_cron_execution.v1.LedgerCronExecutionService/Update';
    const CHECK_FOR_ENTITY_ALERTS           = '/twirp/rzp.settlements.entity_alerts.v1.EntityAlerts/PushForAlert';
    const INITIATE_INTER_NODAL_TRANSFER     = '/twirp/rzp.settlements.inter_nodal_transfer.v1.InterNodalTransfer/Initiate';

    const OPTIMIZER_EXTERNAL_SETTLEMENTS_EXECUTION          = '/twirp/rzp.settlements.optimizer_settlements.v1.ExecuteOptimizerSettlementsAPI/Execute';
    const OPTIMIZER_EXTERNAL_SETTLEMENTS_MANUAL_EXECUTION   = '/twirp/rzp.settlements.optimizer_settlements.v1.ExecuteOptimizerSettlementsAPI/ManualExecute';
    const GET_SETTLEMENT_SOURCE_TRANSACTIONS                = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/FetchSourceTxn';
    const GET_SETTLEMENT_FOR_TRANSACTIONS                   = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/FetchSettlementForTxn';
    const GET_HOLIDAYS_FOR_YEAR_AND_COUNTRY                 = '/twirp/rzp.settlements.holiday.v1.Holiday/Get';
    const POS_TRANSACTIONS_ADD                              = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/RecordBulkPosTransactions';

    const GET_NEXT_SETTLEMENT_AMOUNT    = '/twirp/rzp.settlements.transaction.v1.TransactionService/GetNextSettlementAmount';
    const GET_SETTLEMENT_TIMELINE_MODAL = '/twirp/rzp.settlements.transaction.v1.TransactionService/GetSettlementTimelineModal';

    const SETTLEMENT_INSERT_EXTERNAL_TRANSACTIONS = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/InsertExternalTransaction';
    const SETTLEMENT_UPDATE_TRANSACTIONS_COUNT    = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/UpdateTransactionCountOfExecution';
    const SETTLEMENT_UPDATE_EXECUTION_STATUS      = '/twirp/rzp.settlements.external_transaction.v1.RecordExternalTransactionAPI/UpdateStatusofOptimiserExecution';
    const SETTLEMENT_LEDGER_RECON_TRIGGER         = '/twirp/rzp.settlements.ledger_recon.v1.LedgerReconService/LedgerRecon';

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

    const SERVICE_DASHBOARD              = 'dashboard';
    const SERVICE_REMINDER               = 'reminder';
    const SERVICE_PAYOUT                 = 'payout';
    const SERVICE_API                    = 'api';
    // RSR-1970 merchant dashboard AUTH
    const SERVICE_MERCHANT_DASHBOARD     = 'merchant_dashboard';

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
    protected function parseResponse($response): array
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
    protected function setHeader()
    {
        if( $this->auth->isOptimiserDashboardRequest() === true)
        {
            $this->headers[RequestHeader::X_USER_EMAIL] = $this->getMerchantEmail();
        }
        else {
            $this->headers[RequestHeader::X_USER_EMAIL] = $this->getAdminEmail();
        }
    }

    protected function getAdminEmail(): string
    {
        return $this->auth->getDashboardHeaders()[self::ADMIN_EMAIL] ?? '';
    }

    protected function getMerchantEmail(): string
    {
        return $this->auth->getUser()->getEmail() ?? '';
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
            $this->trace->warning(TraceCode::SETTLEMENTS_REQUEST_EXCEPTION, [
                'status_code'   => $code,
                'response_body' => $body,
            ]);

            throw new Exception\TwirpException($body);
        }
    }

    /**
     * this method returns the bank account request
     *
     * @param       $attributeValue
     * @param       $pattern
     *
     * @param       $replacementCharacter
     * @param mixed ...$attributeLengthRange : array comprised of three values at max. [Max length of param, minimum length , dummy value in case attribute is smaller than minimum length allowed]
     *
     * @return string
     */

    public function getBAAttributeAppropriateToNSS($attributeValue, $pattern,$replacementCharacter, ...$attributeLengthRange)
    {
        //if length smaller than minimum threshold, replace it with dummy
        if ((count($attributeLengthRange) === 3) && (strlen($attributeValue) < $attributeLengthRange[1]))
        {
            $attributeValue= $attributeLengthRange[2];
            return  $attributeValue;
        }

        //if length exceeds threshold, trim blindly to that length
        if ((count($attributeLengthRange) >= 1) && (strlen($attributeValue) > $attributeLengthRange[0]))
        {
            $attributeValue=substr($attributeValue, 0, $attributeLengthRange[0]);
        }

        // if invalid characters or regex fails, remove failing characters
        //need to make sure, length checkers are added before rgex check; since attributes like bene names have complex regex's where ordering exists wrt to characters of different kinds.
        $attributeValue=preg_replace($pattern,$replacementCharacter,'/'.preg_quote($attributeValue).'/'); // nosemgrep : php.lang.security.preg-replace-eval.preg-replace-eval

        //if length exceeds threshold, trim blindly to that length
        //this will not happen in case of bene names, bene mobile.
        //need to check this again, because preg_quote might increase length if replacement character is not ''. Could happen in case of bene city. Post regex fixing the trimming hence will not create problems.
        if ((count($attributeLengthRange) >= 1) && (strlen($attributeValue) > $attributeLengthRange[0]))
        {
            $attributeValue=substr($attributeValue, 0, $attributeLengthRange[0]);
        }

        return $attributeValue;
    }

    public function stringPadInMiddle($attribute,$minLength,$character)
    {
        // if length is lesser than 2
        while (strlen($attribute)<=1)
        {
            // we can throw exception as well,but might cause migration fails. Post data fix , all such merchants will be put on Hold.
           $attribute=$attribute.'0';
        }

        //if length is at-least 2, but less than minimum length. So insert characters in the middle of string till length becomes appropriate.
        //similar to what we do currently
        while (strlen($attribute)<$minLength)
        {
            $attribute = substr_replace($attribute, $character, strlen($attribute)/2, 0);
        }

        return $attribute;
    }

    public function replaceWithDummyForBAAttribute($attribute,$minLength,$maxLength,$pattern,$dummyValue)
    {
        if (strlen($attribute)<$minLength || strlen($attribute)>$maxLength )
        {
            return $dummyValue;
        }
        if (($pattern!=null) && (preg_match($pattern,$attribute)!=1))
        {
            return $dummyValue;
        }
        return $attribute;
    }

    public function getAppropriateBeneNameOfMerchant($beneName)
    {
        //regex : over length , and second : can begin with alphanumeric and end with alphanumeric or '.' Special characters only allowed at the middle.
        // all valid characters which can be present in bene name
        $pattern            ='/[^a-zA-Z0-9-_.&,()\' ]/';

        //only valid characters, should be allowed. So invalid characters are removed, and length trimmed to max 40
        $beneName           = $this->getBAAttributeAppropriateToNSS($beneName,$pattern,'',40);

        $beneName           = ltrim($beneName,'-_.&,()\' ');

        //bene name can terminate with '.'
        $beneName           = rtrim($beneName,'-_&,()\' ');

        $beneName           = (strlen($beneName)<4)?$this->stringPadInMiddle($beneName, 4, ' '):$beneName;

        return $beneName;
    }

    public function getBankAccountCreateRequestForSettlementService($ba, $via = 'payout', $isOrgAccount = false, $merchant = null)
    {
        $beneCityPattern    = '/[^a-zA-Z0-9 _-]/';

        $beneMobilePattern  = '/[^a-zA-Z0-9]/';

        $beneEmailPattern   ='/^([a-zA-Z0-9_+\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$/';

        $dummyBeneMobile    = '9999999999';

        $dummyEmail         = 'razorpayDummy@gmail.com';

        //TODO : Fix at NSS-OSS , Name-Contact length checker of 40 characters. Should be 50
        $beneName           = trim($ba->getBeneficiaryName());

        $beneName           = $this->getAppropriateBeneNameOfMerchant($beneName);

        $beneEmail          = $ba->getBeneficiaryEmail() ?? '';

        $beneEmail          = $this->replaceWithDummyForBAAttribute($beneEmail,5,255,$beneEmailPattern,$dummyEmail);

        // in case there are characters like '+' , '-' replacing and removing such characters.If length inappropriate, replace with dummy mobile
        $beneMobile         = strval($ba->getBeneficiaryMobile())??'';

        $beneMobile         = $this->getBAAttributeAppropriateToNSS($beneMobile,$beneMobilePattern,'');

        $beneMobile         = $this->replaceWithDummyForBAAttribute($beneMobile,10,32,null,$dummyBeneMobile);

        $beneCity           = $ba->getBeneficiaryCity() ?? ' ';

        $beneCity           = $this->getBAAttributeAppropriateToNSS($beneCity,  $beneCityPattern,' ',30);

        $entityIdentifier =  $isOrgAccount ?  'org_id' : 'merchant_id';

        $entityIdentifierValue = $isOrgAccount ?  $ba->getEntityId() : $ba->getMerchantId();

        // adding trimming for account number to remove extra characters on both sides
        $accountNumber      =  trim($ba->getAccountNumber());

        return [
            $entityIdentifier     => $entityIdentifierValue ,
            'account_number'      => $accountNumber,
            'account_type'        => $ba->getAccountType() !== null ? $ba->getAccountType() : 'current',
            'ifsc_code'           => $ba->getIfscCode(),
            'beneficiary_name'    => $beneName,
            'beneficiary_address' => $ba->getBeneficiaryAddress1() ?? '',
            'beneficiary_city'    => $beneCity,
            'beneficiary_state'   => $ba->getBeneficiaryState() ?? '',
            'beneficiary_country' => $ba->getBeneficiaryCountry() ?? '',
            'beneficiary_email'   => $beneEmail,
            'beneficiary_mobile'  => $beneMobile,
            'accepted_currency'   => $merchant !== null ? $merchant->getCurrency() : Currency::INR,
            'extra_info'          => [
                'via' => $via
            ],
        ];
    }
}
