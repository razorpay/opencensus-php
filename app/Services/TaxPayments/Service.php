<?php

namespace RZP\Services\TaxPayments;

use Mail;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Http\Request\Requests;
use RZP\Http\Response\StatusCode;
use RZP\Mail\TaxPayments\GenericTaxPaymentEmail;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Settings\Accessor;
use RZP\Models\Settings\Module;
use RZP\Models\Settings\Service as SettingsService;
use RZP\Models\User\Entity;
use RZP\Models\User\Entity as UserEntity;
use RZP\Trace\TraceCode;

/**
 * Class TaxPayments
 *
 * @package RZP\Services
 * This class is responsible to talk to the TaxPayments APIs that are hosted
 * on the TaxPayments APP
 */
class Service
{
    // MS endpoints
    const BASE_PATH                           = 'twirp/razorpay.vendorpayments.taxpayments.Taxpayments';
    const GET_ALL_SETTINGS                    = 'GetAllSettings';
    const ADD_OR_UPDATE_SETTINGS              = 'AddOrUpdateSettings';
    const ADD_OR_UPDATE_SETTINGS_FOR_AUTO_TDS = 'AddOrUpdateSettingsForAutoTds';
    const GET_TAX_PAYMENT_BY_ID               = 'GetTaxPayment';
    const LIST_TAX_PAYMENTS                   = 'ListTaxPayments';
    const PAY_TAX_PAYMENTS                    = 'PayTaxPayment';
    const INTERNAL_ICICI_ACTION               = 'InternalIciciAction';
    const BULK_PAY_TAX_PAYMENTS               = 'BulkPayTaxPayments';
    const INITIATE_MONTHLY_PAYOUTS            = 'InitiateMonthlyPayouts';
    const CANCEL_QUEUED_PAYOUT_CRON           = 'CancelQueuedPayoutCron';
    const TAX_PAYMENT_ENABLED_KEY             = 'tax_payment_enabled';
    const MONTHLY_SUMMARY                     = 'MonthlySummary';
    const ADD_PENALTY_CRON                    = 'AddPenaltyCron';
    const MARK_AS_PAID                        = 'MarkAsPaid';
    const UPLOAD_CHALLAN                      = 'UploadChallan';
    const UPDATE_CHALLAN_FILE_ID              = 'UpdateChallanFileId';
    const ADMIN_ACTIONS                       = 'AdminActions';
    const EMAIL_CRON                          = 'EmailCron';
    const CREATE_MANUAL_TAX_PAYMENT           = 'CreateManualTaxPayment';
    const CREATE_DIRECT_TAX_PAYMENT           = 'CreateDirectTaxPayment';
    const PG_WEBHOOK_HANDLER                  = 'WebHookHandler';
    const EDIT_MANUAL_TAX_PAYMENT             = 'EditManualTaxPayment';
    const CANCEL_MANUAL_TAX_PAYMENT           = 'CancelManualTaxPayment';
    const GET_TDS_CATEGORIES                  = 'GetTdsCategories';
    const GET_INVALID_TAN_STATUS              = 'GetInvalidTanStatus';
    const GET_DTP_CONFIG                      = 'GetDTPConfig';
    const GET_DOWNTIME_SCHEDULE_BY_MODULE     = 'GetDowntimeScheduleByModule';
    const GET_DOWNTIME_SCHEDULE               = 'GetDowntimeSchedule';
    const ICICI_RETRY_CALLBACK                = 'IciciRetryCallback';
    const FETCH_PENDING_GST                   = 'FetchPendingGst';
    const UFH_BULK_DOWNLOAD                   = 'InitiateBulkChallanDownload';

    // general constants
    const DATA                     = 'data';
    const TEMPLATE_NAME            = 'template_name';
    const SUBJECT                  = 'subject';
    const NAME                     = 'name';
    const TYPE                     = 'type';
    const ACCOUNT_NUMBER           = 'account_number';
    const BALANCE                  = 'balance';
    const MERCHANT_ID              = 'merchant_id';
    const SETTINGS                 = 'settings';
    const BANKING_ACCOUNT          = 'banking_account';
    const MERCHANT_EMAIL           = 'merchant_email';
    const CONTENT_TYPE             = 'Content-Type';
    const X_APP_MODE               = 'X-App-Mode';
    const CC_EMAILS                = 'cc_emails';
    const DROPPING_REQUEST         = 0;
    const DEFAULT_OFFSET           = 0;
    const DEFAULT_LIMIT            = 10;
    const X_RAZORPAY_TASKID_HEADER = 'X-Razorpay-TaskId';
    const X_REQUEST_ID             = 'X-Request-ID';
    const X_MERCHANT_ID            = 'X-Merchant-Id';
    const X_USER_ID                = 'X-User-Id';
    const X_ORG_ID                 = 'X-Org-Id';

    protected $app;

    protected $repo;

    protected $trace;

    protected $config;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->env = $app['env'];

        // we are using the same creds as that of vendor-payments to access the APIs
        $this->config = $app['config']['applications.vendor_payments'];

        $this->repo = $app['repo'];
    }

    public function cancel(MerchantEntity $merchant, string $taxPaymentId, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                                          null,
                                          [
                                              'data'        => $input,
                                              'merchant_id' => $merchant->getPublicId()
                                          ]);
        }

        $input['user_id'] = $user->getPublicId();

        $input['tax_payment_id'] = $taxPaymentId;

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CANCEL_MANUAL_TAX_PAYMENT);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function bulkChallanDownload(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UFH_BULK_DOWNLOAD);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function sendMail(array $input)
    {
        (new Validator())->validateInput(Validator::SEND_MAIL, $input);
        $cc = array_get($input, self::CC_EMAILS, []);

        Mail::queue(new GenericTaxPaymentEmail($input[self::MERCHANT_EMAIL],
                                               $input[self::SUBJECT],
                                               $input[self::TEMPLATE_NAME],
                                               $input[self::DATA],
                                               $cc));

        return ['success' => true];
    }

    public function monthlySummary(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::MONTHLY_SUMMARY);

        return $this->makeRequest($merchant, $url, []);
    }

    public function edit(MerchantEntity $merchant, string $taxPaymentId, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                                          null,
                                          [
                                              'data'        => $input,
                                              'merchant_id' => $merchant->getPublicId()
                                          ]);
        }

        $input['user_id'] = $user->getPublicId();

        $input['tax_payment_id'] = $taxPaymentId;

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EDIT_MANUAL_TAX_PAYMENT);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function create(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                                          null,
                                          [
                                              'data'        => $input,
                                              'merchant_id' => $merchant->getPublicId()
                                          ]);
        }

        $input['user_id'] = $user->getPublicId();

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_MANUAL_TAX_PAYMENT);

        return $this->makeRequest($merchant, $url, $input);
    }
    
    public function internalIciciAction(array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INTERNAL_ICICI_ACTION);

        return $this->makeRequest(null, $url, $input);
    }

    public function addPenalty()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADD_PENALTY_CRON);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    public function mailCron(array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::EMAIL_CRON);

        return $this->makeRequest(null, $url, $input);
    }

    /**
     * This will query the settings service and get all the merchants that have the tax-payment settings enabled
     *
     * @param array $input
     *
     * @return array
     */
    public function settingsOfTaxPaymentEnabledMerchants(array $input)
    {
        (new Validator())->validateInput(Validator::TAX_PAYMENT_ENABLED_MERCHANTS, $input);

        $settings = (new SettingsService())->getSettingsIfKeyPresent(
            Module::TAX_PAYMENTS,
            $input['tax_feature_key'] ?? self::TAX_PAYMENT_ENABLED_KEY,
            "true",
            $input['offset'] ?? self::DEFAULT_OFFSET,
            $input['limit'] ?? self::DEFAULT_LIMIT
        );

        $settingsOfEnabledMerchants = [];

        foreach ($settings as $setting)
        {
            $merchant = $this->repo->merchant->find($setting['entity_id']);

            $settingsAccessor = Accessor::for($merchant, Module::TAX_PAYMENTS);

            $settings = $settingsAccessor->all()->toArray();

            $accountNumber = array_get($settings, 'merchant_auto_debit_account_number', null);

            $bankingAccountInfo = null;

            if (empty($accountNumber) === false)
            {
                $bankingAccount = $this->repo
                    ->banking_account
                    ->findByMerchantAndAccountNumberPublic($merchant, $accountNumber);
                if ($bankingAccount !== null)
                {
                    $bankingAccountInfo = [
                        self::NAME           => $bankingAccount->getBankName(),
                        self::TYPE           => $bankingAccount->getAccountType(),
                        self::ACCOUNT_NUMBER => $bankingAccount->getAccountNumber(),
                        self::BALANCE        => $bankingAccount->balance->getBalance()
                    ];
                }
            }

            array_push($settingsOfEnabledMerchants,
                       [
                           self::MERCHANT_ID     => $merchant->getId(),
                           self::SETTINGS        => $settings,
                           self::BANKING_ACCOUNT => $bankingAccountInfo,
                           self::MERCHANT_EMAIL  => $merchant->getEmail(),
                       ]);
        }

        return $settingsOfEnabledMerchants;
    }

    public function getBooleanValue(string $value): bool
    {
        if (empty($value) === true)
        {
            return false;
        }

        // convert to a json string and then apply json_decode
        $jsonStr = sprintf('{"key" : %s}', strtolower($value));

        $jsonDecoded = json_decode($jsonStr, true);

        if ($jsonDecoded === null)
        {
            return false;
        }

        return $jsonDecoded['key'];

    }

    public function adminActions(array $input)
    {
        // we are expecting a json string in the body here
        $jsonInput = array_pull($input, 'json_data', null);

        if ($jsonInput === null)
        {
            return ['message' => 'empty data'];
        }

        $parsedData = json_decode($jsonInput, true);

        if ($parsedData == null)
        {
            return ['message' => 'json could not be decoded'];
        }

        if (empty($_FILES) === false)
        {
            $parsedData['file'] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

            $parsedData['file_name'] = $_FILES['file']['name'];
        }

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADMIN_ACTIONS);

        return $this->makeRequest(null, $url, $parsedData);
    }

    public function initiateMonthlyPayouts()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::INITIATE_MONTHLY_PAYOUTS);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    public function cancelQueuedPayouts()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CANCEL_QUEUED_PAYOUT_CRON);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    public function payTaxPayment(MerchantEntity $merchant, string $taxPaymentId, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                                          null,
                                          [
                                              'tax_payment_id' => $taxPaymentId,
                                              'merchant_id'    => $merchant->getPublicId()
                                          ]);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::PAY_TAX_PAYMENTS);

        $input['tax_payment_id'] = $taxPaymentId;

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function bulkPayTaxPayment(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user == null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::BULK_PAY_TAX_PAYMENTS);

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getAllSettings(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_ALL_SETTINGS);

        return $this->makeRequest($merchant, $url);
    }

    public function addOrUpdateSettings(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADD_OR_UPDATE_SETTINGS);

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function addOrUpdateSettingsForAutoTds(MerchantEntity $merchant, array $input, UserEntity $user = null)
    {
        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ADD_OR_UPDATE_SETTINGS_FOR_AUTO_TDS);

        $input['user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function listTaxPayments(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::LIST_TAX_PAYMENTS);

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getTdsCategories()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TDS_CATEGORIES);

        return $this->makeRequest(null, $url, ['timestamp' => now()]);
    }

    public function getTaxPayment(MerchantEntity $merchant, string $taxPaymentId, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_TAX_PAYMENT_BY_ID);

        $input['tax_payment_id'] = $taxPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function markAsPaid(MerchantEntity $merchant,
                               array $input,
                               Entity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::MARK_AS_PAID);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $input['manually_paid_user_id'] = $user->getPublicId();

        return $this->makeRequest($merchant, $url, $input);
    }

    public function uploadChallan(MerchantEntity $merchant, array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPLOAD_CHALLAN);
        // The MS we are calling, expects JSON content,
        // so we are sending the contents of the file in
        // base_64 encoded byte array

        $input['file'] = base64_encode(file_get_contents($_FILES['file']['tmp_name']));

        $input['file_name'] = $_FILES['file']['name'];

        return $this->makeRequest($merchant, $url, $input);

    }

    public function webHookHandler(array $input)
    {
        $secret = array_get(getallheaders(), 'X-Razorpay-Signature', '');

        if ($secret == '')
        {
            return self::DROPPING_REQUEST;
        }

        $input['whole_request_payload'] = json_encode($input);

        $input['secret'] = $secret;

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::PG_WEBHOOK_HANDLER);

        return $this->makeRequest(null, $url, $input);
    }

    public function createDirectTaxPayment(array $input)
    {
        if (Environment::isEnvironmentQA($this->env) === false)
        {
            // validate recaptcha
            (new Validator())
                ->setStrictFalse()
                ->validateInput(Validator::CREATE_DIRECT_TAX_PAYMENT, $input);
        }
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::CREATE_DIRECT_TAX_PAYMENT);

        return $this->makeRequest(null, $url, $input);
    }

    public function updateChallanFileId(MerchantEntity $merchant,
                                        string $taxPaymentId,
                                        array $input)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::UPDATE_CHALLAN_FILE_ID);

        $input['tax_payment_id'] = $taxPaymentId;

        return $this->makeRequest($merchant, $url, $input);
    }

    public function getInvalidTanStatus(MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_INVALID_TAN_STATUS);

        return $this->makeRequest($merchant, $url, ['timestamp' => now()]);
    }

    public function getDTPConfig()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_DTP_CONFIG);

        return $this->makeRequest(null, $url, ['timestamp' => now()]);
    }

    public function getDowntimeSchedule(string $module)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_DOWNTIME_SCHEDULE_BY_MODULE);

        $input = ['module' => $module];

        return $this->makeRequest(null, $url, $input);
    }

    public function listDowntimeSchedule()
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::GET_DOWNTIME_SCHEDULE);

        return $this->makeRequest(null, $url, ['time' => now()]);
    }

    public function reminderCallback(string $mode, string $entityType, string $entityId)
    {
        $input['entity_id'] = $entityId;

        $input['entity_type'] = $entityType;

        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::ICICI_RETRY_CALLBACK);

        return $this->makeRequest(null, $url, $input, [], 'POST', $mode);
    }

    public function fetchPendingGstPayments(MerchantEntity $merchant, UserEntity $user = null)
    {
        $url = sprintf('%s/%s/%s', $this->config['url'], self::BASE_PATH, self::FETCH_PENDING_GST);

        if ($user === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST);
        }

        $data = [
            'user_id' => $user->getPublicId(),
        ];

        return $this->makeRequest($merchant, $url, $data);
    }

    protected function makeRequest(MerchantEntity $merchant = null,
                                   string $url = '',
                                   array $data = [],
                                   array $headers = [],
                                   string $method = 'POST',
                                   string $mode = '')
    {
        if ($merchant !== null)
        {
            $data[self::MERCHANT_ID] = $merchant->getId();
        }

        $headers[self::CONTENT_TYPE] = 'application/json';

        $headers[self::X_RAZORPAY_TASKID_HEADER] = $this->app['request']->getTaskId();

        $headers[self::X_REQUEST_ID] = $this->app['request']->getId();

        $headers[self::X_MERCHANT_ID] = $this->app['basicauth']->getMerchantId();

        $headers[self::X_USER_ID] = optional($this->app['basicauth']->getUser())->getId() ?? '';

        $headers[self::X_ORG_ID] = $this->app['basicauth']->getOrgId();

        $options = [
            'auth'    => ['api', $this->config['secret']],
            'timeout' => $this->config['timeout']
        ];

        if ($mode !== '')
        {
            $headers[self::X_APP_MODE] = $mode;
        }
        else
        {
            $rzpMode = array_get($this->app, 'rzp.mode', null);

            if ($rzpMode)
            {
                $headers[self::X_APP_MODE] = $this->app['rzp.mode'] ? $this->app['rzp.mode'] : Mode::LIVE;
            }
        }

        $this->trace->info(TraceCode::TAX_PAYMENT_REQUEST,
                           [
                               'url' => $url
                           ]);

        $response = Requests::request(
            $url,
            $headers,
            json_encode($data),
            $method,
            $options);

        $responseBody = json_decode($response->body, true);

        if ($response->status_code !== StatusCode::SUCCESS)
        {
            $description = "";

            if ($responseBody !== null)
            {
                $description = array_pull($responseBody, 'msg', $responseBody);
            }
            else
            {
                $description = "received empty response";
            }

            throw new BadRequestException(ErrorCode::BAD_REQUEST_VENDOR_PAYMENT_MICRO_SERVICE_FAILED,
                                          null,
                                          $description,
                                          $description);
        }

        return $responseBody;
    }
}
