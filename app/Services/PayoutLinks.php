<?php

namespace RZP\Services;

use Config;

use Requests;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use RZP\Constants\Environment;
use RZP\Models\FundAccount\Type;
use RZP\Models\Merchant;
use RZP\Http\Response\StatusCode;
use RZP\Models\PayoutLink\Entity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\PayoutLink\Validator;
use RZP\Models\Batch\Type as BatchType;
use RZP\Exception\BadRequestException;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Batch\Core as BatchCore;
use RZP\Models\User\Entity as UserEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class PayoutLinks
 * @package RZP\Services
 *
 * No validations will happen here.
 * This will just call the right endpoints and return the responses as is
 * If there is an error thrown from the MicroService, that same error with
 * the right error code will be sent back to the caller
 *
 */
class PayoutLinks
{
    const KEY                                      = 'api';
    const BATCH_ID                                 = 'batch_id';
    const MERCHANT_ID                              = 'merchant_id';
    const PAYOUT_LINK_ID                           = 'payout_link_id';
    const CREATE_PAYOUT_LINK_PATH                  = 'twirp/payoutlinks.Payoutlinks/CreatePayoutLink';
    const CANCEL_PAYOUT_LINK_PATH                  = 'twirp/payoutlinks.Payoutlinks/CancelPayoutLink';
    const FETCH_PAYOUT_LINK_PATH                   = 'twirp/payoutlinks.Payoutlinks/FetchPayoutLink';
    const FETCH_PAYOUT_LINK_MULTIPLE_PATH          = 'twirp/payoutlinks.Payoutlinks/FetchMultiplePayoutLinks';
    const GET_SETTINGS_PAYOUT_LINK_PATH            = 'twirp/payoutlinks.Payoutlinks/GetSettings';
    const UPDATE_SETTINGS_PAYOUT_LINK_PATH         = 'twirp/payoutlinks.Payoutlinks/UpdateSettings';
    const PAYOUT_LINK_GENERATE_OTP_PATH            = 'twirp/payoutlinks.Payoutlinks/GenerateOTP';
    const RESEND_BULK_NOTIFICATION_PATH            = 'twirp/payoutlinks.Payoutlinks/ResendBulkNotification';
    const PAYOUT_LINK_GET_FUND_ACCOUNTS_BY_CONTACT = 'twirp/payoutlinks.Payoutlinks/GetFundAccountsByContact';
    const PAYOUT_LINK_VERIFY_OTP_PATH              = 'twirp/payoutlinks.Payoutlinks/VerifyOTP';
    const PAYOUT_STATUS_UPDATE                     = 'twirp/payoutlinks.Payoutlinks/UpdatePayoutLinkStatus';
    const INITIATE_PAYOUT_LINK_PATH                = 'twirp/payoutlinks.Payoutlinks/InitiatePayoutLink';
    const GET_HOSTED_PAGE_DATA                     = 'twirp/payoutlinks.Payoutlinks/GetHostedPageData';
    const RESEND_NOTIFICATION                      = 'twirp/payoutlinks.Payoutlinks/ResendNotification';
    const ON_BOARDING_STATUS                       = 'twirp/payoutlinks.Payoutlinks/OnboardingStatus';
    const CREATE_BATCH                             = 'twirp/payoutlinks.Payoutlinks/CreateBatchPayoutLinks';
    const BATCH_SUMMARY                            = 'twirp/payoutlinks.Payoutlinks/GetBatchSummary';
    const SUMMARY                                  = 'twirp/payoutlinks.Payoutlinks/Summary';
    const ADMIN_ACTIONS                            = 'twirp/payoutlinks.Payoutlinks/AdminActions';
    const BATCH_PL_PROCESSED                       = 'batch_payout_links_processed';
    const BATCH_PL_INITIATED                       = 'batch_payout_links_initiated';
    const BATCH_PL_COUNT                           = 'batch_payout_links_count';
    const BATCH_REQUEST_ROWS                       = 'batch_request_rows';
    const FUND_ACCOUNT_ID                          = 'fund_account_id';
    const ACCOUNT_NUMBER                           = 'account_number';
    const CANCELLED_AT                             = 'cancelled_at';
    const UPDATED_AT                               = 'updated_at';
    const SEND_SMS                                 = 'send_sms';
    const SEND_EMAIL                               = 'send_email';
    const ATTEMPT_COUNT                            = 'attempt_count';
    const PAYOUTS                                  = 'payouts';
    const USER_ID                                  = 'user_id';
    const USER                                     = 'user';
    const RECEIPT                                  = 'receipt';
    const MODE                                     = 'mode';
    const NOTES                                    = 'notes';
    const COUNT                                    = 'count';
    const ITEMS                                    = 'items';

    const INVALID_REQUEST_ERROR_MSG                = 'the json request could not be decoded';
    const INVALID_REQUEST_RESPONSE_MSG             = 'Invalid request payload';
    const TEST_MODE_ERROR_MESSAGE                  = 'Test Mode is currently not supported for Payout Links';

    const DASHBOARD_INTERNAL                       = 'DASHBOARD_INTERNAL';

    protected $baseUrl;

    protected $secret;

    protected $repo;

    protected $config;

    protected $trace;

    protected $proxy;

    protected $mode;

    protected $merchant;

    protected $app;

    protected $walletService;

    public function __construct($app)
    {
        $this->trace = $app['trace'];

        $this->config = $app['config'];

        $payoutLinkConfig = $this->config->get('applications.payout_links');

        $this->baseUrl = $payoutLinkConfig['micro_service_endpoint'];

        $this->secret  = $payoutLinkConfig['secret'];

        $this->repo  = $app['repo'];

        $this->app = $app;
    }

    public function create(MerchantEntity $merchant, array $input): array
    {
        $this->rzpModeCheck($merchant->getId());

        $this->trace->info(TraceCode::PAYOUT_LINK_CREATE_REQUEST,
            $input);

        $url = sprintf('%s/%s', $this->baseUrl, self::CREATE_PAYOUT_LINK_PATH);

        $sendSms = array_pull($input, self::SEND_SMS, "false");

        $sendMail = array_pull($input, self::SEND_EMAIL, "false");

        if(array_key_exists(self::NOTES, $input) === true)
        {
            $notes = $input[self::NOTES];

            if(is_array($notes) === false)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_NOTES_SHOULD_BE_ARRAY, null, null);
            }
        }

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::SEND_SMS] = strval($sendSms);

        $input[self::SEND_EMAIL] = strval($sendMail);

        $response = $this->makeRequest($url, $input);

        $expandArray = [0 => self::USER];

        $this->processParameters($response, false, $expandArray);

        return $response;
    }

    public function getSettings(string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SETTINGS_GET,
            [
                $merchantId
            ]);

        $url = $this->getConstructedUrl(self::GET_SETTINGS_PAYOUT_LINK_PATH);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        $response = $this->makeRequest($url, $request);

        $settings = array_pull($response, self::MODE, []);

        $this->processSettingsParameters($settings);

        return $settings;
    }

    public function updateSettings(string $merchantId, array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_SETTINGS_GET,
            [
                $merchantId
            ]);

        $url = $this->getConstructedUrl(self::UPDATE_SETTINGS_PAYOUT_LINK_PATH);

        $request = [
            self::MERCHANT_ID  => $merchantId,
            self::MODE         => $input
        ];

        $oldSettings = $this->getSettings($merchantId);

        $response = $this->makeRequest($url, $request);

        $newSettings = array_pull($response, self::MODE, []);

        $this->processSettingsParameters($newSettings);

        $this->notifySettingsChangeOnSlack($merchantId, $oldSettings, $newSettings);

        return $newSettings;
    }

    public function cancel(string $payoutLinkId, string $merchantId)
    {
        $this->rzpModeCheck($merchantId);

        $this->trace->info(TraceCode::PAYOUT_LINK_CANCEL_REQUEST,
            [
                $payoutLinkId
            ]);

        $url = $this->getConstructedUrl(self::CANCEL_PAYOUT_LINK_PATH);

        $request = [
            self::PAYOUT_LINK_ID => $payoutLinkId,
            self::MERCHANT_ID    => $merchantId
        ];

        $response = $this->makeRequest($url, $request);

        $this->processParameters($response);

        return $response;
    }

    public function fetch(string $payoutLinkId, array $input, string $merchantId = "")
    {
        $this->rzpModeCheck($merchantId);

        $forAdminResponse = true;

        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINK_PATH);

        $input[self::PAYOUT_LINK_ID] = $this->appendPublicSignForPayoutLink($payoutLinkId);

        if($merchantId != "")
        {
            $forAdminResponse = false;
            $input[self::MERCHANT_ID] = $merchantId;
        }

        $expandArray = array_pull($input, 'expand', []);

        $input['expand'] = $expandArray;

        $response = $this->makeRequest($url, $input);

        $this->processParameters($response, $forAdminResponse, $expandArray);

        return $response;
    }

    public function fetchMultiple(array $input)
    {
        $this->rzpModeCheck();

        $url = $this->getConstructedUrl(self::FETCH_PAYOUT_LINK_MULTIPLE_PATH);

        if(key_exists('id', $input))
        {
            $payoutlinkid = $input['id'];
            $payoutlinkid = $this->appendPublicSignForPayoutLink($payoutlinkid);

            $input[self::PAYOUT_LINK_ID] = $payoutlinkid;
        }

        $expandArray = [];

        if (key_exists('expand', $input))
        {
            $expandArray = $input['expand'];
        }

        $response = $this->makeRequest($url, $input);

        $response[self::COUNT] = array_pull($response, self::COUNT, 0);

        $response[self::ITEMS] = array_pull($response, self::ITEMS, []);

        $payoutlinks = &$response["items"];

        foreach ($payoutlinks as &$value)
        {
            $this->processParameters($value, false, $expandArray);
        }

        return $response;
    }

    public function getModeAndMerchant(string $payoutLinkId)
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_GET_HOSTED_PAGE_DATA,
            [
                $payoutLinkId
            ]);

        $url = sprintf('%s/%s', $this->baseUrl, self::GET_HOSTED_PAGE_DATA);

        $request = [
            self::PAYOUT_LINK_ID => 'poutlk_' . $payoutLinkId
        ];

        $response =  $this->makeRequest($url, $request);

        return [Mode::LIVE, $response['settings'][self::MERCHANT_ID]];
    }

    public function initiate(MerchantEntity $merchant, array $input, string $payoutLinkId): array
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_INITIATE_REQUEST,
            $input);

        $url = sprintf('%s/%s', $this->baseUrl, self::INITIATE_PAYOUT_LINK_PATH);

        $input[self::MERCHANT_ID] = $merchant->getId();

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function generateAndSendCustomerOtp(string $payoutLinkId, array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_LINK_CUSTOMER_OTP_GENERATE,
            $input);

        $url = $this->getConstructedUrl(self::PAYOUT_LINK_GENERATE_OTP_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function getFundAccountsOfContact(string $payoutLinkId, array $input): array
    {
        $url = $this->getConstructedUrl(self::PAYOUT_LINK_GET_FUND_ACCOUNTS_BY_CONTACT);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        $response = $this->makeRequest($url, $input);

        $response[self::COUNT] = array_pull($response, self::COUNT, 0);

        $response[self::ITEMS] = array_pull($response, self::ITEMS, []);

        return $response;
    }

    public function getHostedPageData(string $payoutLinkId, MerchantEntity $merchant)
    {
        $url = sprintf('%s/%s', $this->baseUrl, self::GET_HOSTED_PAGE_DATA);

        $request = [
            self::PAYOUT_LINK_ID => $payoutLinkId
        ];

        $response = $this->makeRequest($url, $request);

        $payoutUtr = null;

        $payoutMode = null;

        $payoutLinkInfo = $response['payout_link_response'];

        if (key_exists('payouts', $payoutLinkInfo)
            && key_exists('count', $payoutLinkInfo['payouts']))
        {
            $payoutsCount = $payoutLinkInfo['payouts']['count'];

            if ($payoutsCount > 0)
            {
                $payouts = $payoutLinkInfo['payouts']['items'];

                $payoutUtr = $payouts[0]['utr'];

                $payoutMode = $payouts[0]['mode'];
            }
        }

        $settings = array_pull($response['settings'], self::MODE, []);

        $allowUpi = $this->allowUpi($payoutLinkInfo, $settings, $merchant);

        // allow amazon pay
        $allowAmazonPay = $this->allowAmazonPay($payoutLinkInfo, $settings, $merchant);

        $isProduction = $this->getEnvironment() === Environment::PRODUCTION;

        $fundAccountDetails = $this->extractFundAccountDetails($payoutLinkInfo, $merchant);

        $contact = $payoutLinkInfo['contact'];

        $data = [
            'api_host'                    => $this->config['url.api.production'],
            'payout_link_id'              => $payoutLinkInfo['id'],
            'payout_link_status'          => $payoutLinkInfo['status'],
            'amount'                      => $payoutLinkInfo['amount'],
            'currency'                    => $payoutLinkInfo['currency'],
            'description'                 => $payoutLinkInfo['description'],
            'user_name'                   => $contact['name'] ?? null,
            'user_email'                  => mask_email($contact['email'] ?? null),
            'user_phone'                  => mask_phone($contact['contact'] ?? null),
            'receipt'                     => $payoutLinkInfo['receipt'] ?? null,
            'merchant_logo_url'           => $merchant->getFullLogoUrlWithSize(),
            'primary_color'               => $merchant->getBrandColorElseDefault(),
            'merchant_name'               => $merchant->getBillingLabel(),
            'allow_upi'                   => $allowUpi,
            'allow_amazon_pay'            => $allowAmazonPay,
            'banking_url'                 => $this->config['applications.banking_service_url'],
            'is_production'               => $isProduction,
            'fund_account_details'        => json_encode($fundAccountDetails),
            'purpose'                     => $payoutLinkInfo['purpose'] ?? null,
            'payout_utr'                  => $payoutUtr,
            'payout_mode'                 => $payoutMode,
            'payout_links_custom_message' => $settings[Entity::CUSTOM_MESSAGE] ?? null,
            'support_contact'             => $settings[Entity::SUPPORT_CONTACT] ?? null,
            'support_email'               => $settings[Entity::SUPPORT_EMAIL] ?? null,
            'support_url'                 => $settings[Entity::SUPPORT_URL] ?? null
        ];

        return $data;
    }

    protected function getEnvironment() {
        return $this->app->environment();
    }

    public function pushPayoutStatus($payoutLinkId, $payoutId, $payoutStatus)
    {
        $this->rzpModeCheck();

        $input = [
            'payout_link_id'  => $payoutLinkId,
            'payout_id'       => $payoutId,
            'payout_status'   => strtoupper($payoutStatus)
        ];

        $url = $this->getConstructedUrl(self::PAYOUT_STATUS_UPDATE);

        return $this->makeRequest($url, $input);
    }

    public function verifyCustomerOtp(string $payoutLinkId, array $input): array
    {
        $url = $this->getConstructedUrl(self::PAYOUT_LINK_VERIFY_OTP_PATH);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;

        return $this->makeRequest($url, $input);
    }

    public function resendNotification(string $payoutLinkId, array $input)
    {
        $this->rzpModeCheck();

        $url = $this->getConstructedUrl(self::RESEND_NOTIFICATION);

        $input[self::PAYOUT_LINK_ID] = $payoutLinkId;
        if (key_exists(self::SEND_SMS, $input) === true)
        {
            if(is_bool($input[self::SEND_SMS]))
            {
                $input[self::SEND_SMS] = $input[self::SEND_SMS] ? 'true' : 'false';
            }
            else
            {
                $input[self::SEND_SMS] = strval($input[self::SEND_SMS]);
            }
        }
        if (key_exists(self::SEND_EMAIL, $input) === true)
        {
            if(is_bool($input[self::SEND_EMAIL]))
            {
                $input[self::SEND_EMAIL] = $input[self::SEND_EMAIL] ? 'true' : 'false';
            }
            else
            {
                $input[self::SEND_EMAIL] = strval($input[self::SEND_EMAIL]);
            }
        }

        return $this->makeRequest($url, $input);
    }

    public function onBoardingStatus(string $merchantId)
    {
        $url = $this->getConstructedUrl(self::ON_BOARDING_STATUS);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        return $this->makeRequest($url, $request);
    }

    public function summary(string $merchantId)
    {
        $this->rzpModeCheck($merchantId);

        $url = $this->getConstructedUrl(self::SUMMARY);

        $request = [
            self::MERCHANT_ID => $merchantId
        ];

        return $this->makeRequest($url, $request);
    }

    public function adminActions(array $input)
    {
        $this->rzpModeCheck();

        $jsonInput = array_pull($input, 'json_data', null);

        if ($jsonInput === null )
        {
            return ['message' => 'empty data'];
        }

        $parsedData = json_decode($jsonInput, true);

        if ($parsedData == null)
        {
            return ['message' => 'json could not be decoded'];
        }

        $url = $this->getConstructedUrl(self::ADMIN_ACTIONS);

        return $this->makeRequest($url, $parsedData);
    }

    public function processBatch(array $input)
    {
        $this->rzpModeCheck();

        $merchantId = $this->app['request']->header(RequestHeader::X_ENTITY_ID) ?? null;

        $userId = $this->app['request']->header(RequestHeader::X_DASHBOARD_USER_ID) ?? null;

        $batchId = $this->app['request']->header(RequestHeader::X_Batch_Id, null);

        if (empty($batchId) === true)
        {
            throw new BadRequestValidationFailureException(
                ErrorCode::BAD_REQUEST_BATCH_ID_MISSING_FOR_PAYOUT_LINK_PROCESS_BATCH
            );
        }

        $this->trace->info(TraceCode::PAYOUT_LINK_PROCESS_BATCH_REQUEST,
            [
                self::MERCHANT_ID => $merchantId,
                self::BATCH_ID => $batchId,
                'input'          => $input,
                'temp_user_id'=> $userId,
            ]);

        $request[self::BATCH_ID] = $batchId;

        $request[self::MERCHANT_ID] = $merchantId;

        $request[self::BATCH_REQUEST_ROWS] = $input;

        $url = $this->getConstructedUrl(self::CREATE_BATCH);

        return $this->makeRequest($url, $request);
    }

    public function getBatchSummary(string $merchantId, string $batchId)
    {
        $this->rzpModeCheck();

        $request[self::BATCH_ID] = $batchId;

        $request[self::MERCHANT_ID] = $merchantId;

        $url = $this->getConstructedUrl(self::BATCH_SUMMARY);

        $response = $this->makeRequest($url, $request);

        $response[self::BATCH_PL_COUNT] = array_pull($response, self::BATCH_PL_COUNT, 0);

        $response[self::BATCH_PL_INITIATED] = array_pull($response, self::BATCH_PL_INITIATED, 0);

        $response[self::BATCH_PL_PROCESSED] = array_pull($response, self::BATCH_PL_PROCESSED, 0);

        return $response;
    }

    public function bulkResendNotification(array $input)
    {
        $url = $this->getConstructedUrl(self::RESEND_BULK_NOTIFICATION_PATH);

        $response = $this->makeRequest($url, $input);

        return $response;
    }

    public function createBatch(array $input, MerchantEntity $merchant, UserEntity $user): array
    {
        $plValidator = new Validator();

        $plValidator->setStrictFalse();

        $plValidator->validateInput(Validator::BATCH_CREATE, $input);

        // only creating payout_link_bulk type batch
        if($input['type'] === BatchType::PAYOUT_LINK_BULK)
        {
            $batch = (new BatchCore)->create($input, $merchant, $user);

            return $batch->toArrayPublic();
        }
        else
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_BATCH_TYPE_FOR_PAYOUT_LINK_CREATE_BATCH,
                null,
                [
                    Entity::MERCHANT_ID     => $merchant->getId(),
                    'type'                  => $input['type']
                ]
            );
        }
    }

    /**
     * 1. Setting is enabled
     * 2. Is not RBL
     * 3. Amount less than 1 lac
     * @param array $payoutLinkInfo
     * @param array $settings
     * @param MerchantEntity $merchant
     * @return bool
     */
    protected function allowUpi(array $payoutLinkInfo, array $settings, MerchantEntity $merchant)
    {
        $channelSupportsUpi = true;

        $upiEnabledInSettings = ((key_exists('UPI', $settings) === true) and
            (boolval($settings['UPI']) === true));

        $bankingAccount = $this->getBankingAccountInfo($merchant, $payoutLinkInfo);

        if ($bankingAccount->getChannel() === Channel::RBL)
        {
            $channelSupportsUpi = false;
        }

        $amountLessThanLac = $payoutLinkInfo['amount'] <= Validator::MAX_UPI_AMOUNT ? true : false;

        return $upiEnabledInSettings and $channelSupportsUpi and $amountLessThanLac;
    }

    protected function allowAmazonPay(array $payoutLinkInfo, array $settings, MerchantEntity $merchant) {
        // Amazon pay will only be enabled for merchants that have the experiment enabled
        if($this->getAmazonPayWalletExperienceEnabled($merchant)) {
            $channelSupportsAmazonPay = true;

            $amazonPayEnabledInSettings = ((key_exists(Entity::AMAZON_PAY, $settings) === true) and
                (boolval($settings[Entity::AMAZON_PAY]) === true));

            $bankingAccount = $this->getBankingAccountInfo($merchant, $payoutLinkInfo);

            if ($bankingAccount->getChannel() === Channel::RBL)
            {
                $channelSupportsAmazonPay = false;
            }

            $amountLessThanEqualTenThousand = $payoutLinkInfo['amount'] <= Validator::MAX_AMAZON_PAY_AMOUNT;

            return $amazonPayEnabledInSettings and $channelSupportsAmazonPay and $amountLessThanEqualTenThousand;
        }
        return false;
    }

    protected function getBankingAccountInfo(MerchantEntity $merchant, array $payoutLinkInfo) {
        return $this->repo
            ->banking_account
            ->findByMerchantAndAccountNumberPublic($merchant, $payoutLinkInfo['account_number']);
    }

    protected function getAmazonPayWalletExperienceEnabled(MerchantEntity $merchant) {
        $variant = $this->app['razorx']->getTreatment($merchant->getId(),
            Merchant\RazorxTreatment::ENABLE_WALLET_ACCOUNT_AMAZON_PAYOUT,
            Mode::LIVE
        );
        return $variant === 'on';
    }

    protected function extractFundAccountDetails(array $payoutLinkInfo, MerchantEntity $merchant)
    {
        if (array_key_exists('fund_account_id' , $payoutLinkInfo) === false)
        {
            return null;
        }

        $fundAccountId = $payoutLinkInfo['fund_account_id'];

        $fundAccount = $this->repo->fund_account->findByPublicIdAndMerchant($fundAccountId, $merchant);

        return $this->getMaskedFundAccountDetails($fundAccount);
    }

    /**
     * Masks the VPA details before sending to the front-end
     * todo, pl Need to move to VPA/Entity [https://razorpay.atlassian.net/browse/RX-1343]
     *
     * @param FundAccountEntity|null $fundAccount
     * @return array|null
     */
    protected function getMaskedFundAccountDetails(FundAccountEntity $fundAccount = null)
    {
        $percentageToMask = '0.7';

        if ($fundAccount === null)
        {
            return null;
        }

        $details = $fundAccount->toArrayPublic();

        $type = $fundAccount->getAccountType();

        switch ($type)
        {
            case Type::VPA:
                $address = $details[Type::VPA][VpaEntity::USERNAME];

                $handle = $details[Type::VPA][VpaEntity::HANDLE];

                $addressLen = strlen($address);

                $handleLen = strlen($handle);

                $lengthOfHandleToMask = ceil($handleLen * $percentageToMask);

                $lengthOfAddressToMask = ceil($addressLen * $percentageToMask);

                $maskedAddress = substr($address, 0, $addressLen - $lengthOfAddressToMask) .
                    str_repeat('*', $lengthOfAddressToMask);

                $maskedHandle = substr($handle, 0, $handleLen - $lengthOfHandleToMask) .
                    str_repeat('*', $lengthOfHandleToMask);

                $details[Type::VPA][VpaEntity::ADDRESS] = sprintf('%s@%s', $maskedAddress, $maskedHandle);

                $details[Type::VPA][VpaEntity::HANDLE] = $maskedHandle;

                $details[Type::VPA][VpaEntity::USERNAME] = $maskedAddress;
        }

        return $details;
    }

    protected function makeRequest(string $url,
                                   array $data,
                                   array $headers = [],
                                   string $method = 'POST')
    {
        $headers['Content-Type'] = 'application/json';

        $headers['X-Task-ID'] = $this->app['request']->getId();

        $options = [
            'auth' => [
                self::KEY,
                $this->secret
            ],
            // Increasing timeout to 25 seconds. Temporary fix.
            // Final FIX: https://jira.corp.razorpay.com/browse/RX-4320
            'timeout' => 25,
        ];

        if (strpos($url, "Payoutlinks/VerifyOTP") !== false) {
            $this->trace->info(TraceCode::PAYOUT_LINKS_REQUEST,
                [
                    'headers' => $headers,
                    'url' => $url
                ]);
        } else {
            $this->trace->info(TraceCode::PAYOUT_LINKS_REQUEST,
                [
                    'headers' => $headers,
                    'url' => $url,
                    'data' => $data,
                ]);
        }

        $response = Requests::$method(
            $url,
            $headers,
            json_encode($data, JSON_FORCE_OBJECT),
            $options);

        $responseBody = json_decode($response->body, true);

        $this->trace->info(TraceCode::PAYOUT_LINKS_RESPONSE,
            [
                'response' => $responseBody
            ]);

        if ($response->status_code !== StatusCode::SUCCESS)
        {
            if(empty($responseBody) === true)
            {
                $description = self::INVALID_REQUEST_RESPONSE_MSG;
            }
            else
            {
                $description = array_pull($responseBody, 'msg', $responseBody);

                if(strpos($description, self::INVALID_REQUEST_ERROR_MSG) !== false)
                {
                    $description = self::INVALID_REQUEST_RESPONSE_MSG;
                }
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_MICRO_SERVICE_FAILED,
                null,
                null,
                $description);
        }
        return json_decode($response->body, true);
    }

    protected function getConstructedUrl(string $path)
    {
        return $url = sprintf('%s/%s', $this->baseUrl, $path);
    }


    /**
     * @param array $payoutLink
     * @param string $operation
     * @param array $expandArray format : ["0":"payouts","1":"user"...]
     */
    protected function processParameters(array &$payoutLink, bool $forAdminResponse = false, array $expandArray = [])
    {
        $payoutLink[self::FUND_ACCOUNT_ID] = array_pull($payoutLink, self::FUND_ACCOUNT_ID, null);

        $payoutLink[self::CANCELLED_AT] = array_pull($payoutLink, self::CANCELLED_AT, null);

        $payoutLink[self::ATTEMPT_COUNT] = array_pull($payoutLink, self::ATTEMPT_COUNT, 0);

        $isPayoutInExpandArray = false;

        $isUserInExpandArray = false;

        foreach ($expandArray as $key => $value) {
            if(strpos($value, self::PAYOUTS) !== false)
            {
                $isPayoutInExpandArray = true;
            }
            if(strpos($value, self::USER) !== false)
            {
                $isUserInExpandArray = true;
            }
        }

        if ($isPayoutInExpandArray === true && sizeof($payoutLink[self::PAYOUTS]) === 0)
        {
            $payoutLink[self::PAYOUTS] = [
                'entity' => 'collection',
                'count' => 0,
                'items' => [],
            ];
        }

        if ($isPayoutInExpandArray === false)
        {
            unset($payoutLink[self::PAYOUTS]);
        }

        if ($isUserInExpandArray === true && sizeof($payoutLink[self::USER]) === 0)
        {
            $payoutLink[self::USER] = null;
        }

        if ($isUserInExpandArray === false)
        {
            unset($payoutLink[self::USER]);
        }

        $payoutLink[self::USER_ID] = array_pull($payoutLink, self::USER_ID, null);

        $payoutLink[self::RECEIPT] = array_pull($payoutLink, self::RECEIPT, null);

        $payoutLink[self::NOTES] = array_pull($payoutLink, self::NOTES, []);

        $payoutLink[self::SEND_SMS] = filter_var($payoutLink[self::SEND_SMS], FILTER_VALIDATE_BOOLEAN);

        $payoutLink[self::SEND_EMAIL] = filter_var($payoutLink[self::SEND_EMAIL], FILTER_VALIDATE_BOOLEAN);

        unset($payoutLink[self::ACCOUNT_NUMBER]);

        $payoutLink[Entity::CONTACT][Entity::NAME] = $payoutLink[Entity::CONTACT][Entity::NAME] ?? null;

        $payoutLink[Entity::CONTACT][Entity::EMAIL] = $payoutLink[Entity::CONTACT][Entity::EMAIL] ?? null;

        $payoutLink[Entity::CONTACT][Entity::CONTACT] = $payoutLink[Entity::CONTACT][Entity::CONTACT] ?? null;

        if ($forAdminResponse === false)
        {
            unset($payoutLink[self::UPDATED_AT]);

            unset($payoutLink[self::MERCHANT_ID]);
        }
        else
        {
            $payoutLink[Entity::CONTACT_NAME] = $payoutLink[Entity::CONTACT][Entity::NAME];

            $payoutLink[Entity::CONTACT_EMAIL] = $payoutLink[Entity::CONTACT][Entity::EMAIL];

            $payoutLink[Entity::CONTACT_PHONE_NUMBER] = $payoutLink[Entity::CONTACT][Entity::CONTACT];

            $payoutLink[Entity::ADMIN] = true;
        }

    }

    protected function processSettingsParameters(array &$settings)
    {
        $impsValue = array_pull($settings, Entity::IMPS, "true");

        $settings[Entity::IMPS] = boolval($impsValue);

        $upiValue = array_pull($settings, Entity::UPI, "true");

        $settings[Entity::UPI] = boolval($upiValue);

        $amazonPayValue = array_pull($settings, Entity::AMAZON_PAY, "true");

        $settings[Entity::AMAZON_PAY] = boolval($amazonPayValue);
    }

    protected function appendPublicSignForPayoutLink(string $payoutlinkid) : string
    {
        if(!str_contains($payoutlinkid, 'poutlk_'))
        {
            return 'poutlk_' . $payoutlinkid;
        }
        else
        {
            return $payoutlinkid;
        }
    }

    protected function notifySettingsChangeOnSlack(string $merchantId, array $oldSettings, array $newSettings)
    {
        $validKeysForSlackNotification = [Entity::IMPS, Entity::UPI, Entity::AMAZON_PAY];

        foreach ($validKeysForSlackNotification as $key)
        {
            //not stopping the UpdateSettings request Logging the error
            try
            {
                if(array_key_exists($key, $newSettings) === true)
                {
                    $isValueChanged = $this->isValueChanged($key, $oldSettings, $newSettings);

                    if($isValueChanged === true)
                    {
                        $this->sendSlackNotification($merchantId, $key, $newSettings[$key]);
                    }
                }
            }
            catch (\Exception $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::PAYOUT_LINK_SETTINGS_SLACK_NOTIFICATION_FAILED,
                    [
                        'failed_slack_notification_key' => $key,
                        'merchant_id' => $merchantId,
                    ]);
            }

        }
    }

    protected function isValueChanged(string $key, array $oldArray, array $newArray)
    {
        $isValueChanged = false;

        $oldArrayValue = array_pull($oldArray, $key);

        $newArrayValue = array_pull($newArray, $key, $oldArrayValue);

        if ($oldArrayValue !== $newArrayValue)
        {
            $isValueChanged = true;
        }

        return $isValueChanged;
    }

    protected function sendSlackNotification(string $merchantId, string $key, $newValue)
    {
        $message = 'Payout Mode ';

        $message .= $key;

        if (boolval($newValue) === true)
        {
            $message .= ' enabled for ';
        }
        else
        {
            $message .= ' disabled for ';
        }

        $user = $this->getInternalUsernameOrEmail();

        $messageUser = self::DASHBOARD_INTERNAL;

        if($user !== self::DASHBOARD_INTERNAL)
        {
            $messageUser = 'Merchant User';
        }

        $message .= $merchantId . ' by ' . $messageUser;

        $this->trace->info(
            TraceCode::PAYOUT_LINK_SETTINGS_UPDATE_SLACK_NOTIFICATION,
            [
                'merchant_id' => $merchantId,
                'message'     => $message
            ]
        );

        $this->app['slack']->queue(
            $message,
            [],
            [
                'channel'  => Config::get('slack.channels.operations_log'),
                'username' => 'Jordan Belfort',
                'icon'     => ':boom:'
            ]
        );
    }

    private function getInternalUsernameOrEmail()
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        return $dashboardInfo['admin_username'] ?? $dashboardInfo['user_email'] ?? self::DASHBOARD_INTERNAL;
    }

    private function rzpModeCheck(string $merchantId = "")
    {
        if($this->app['rzp.mode'] === Mode::TEST)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_LINK_NOT_SUPPORTED_FOR_TEST_MODE,
                null,
                [
                    Entity::MERCHANT_ID     => $merchantId
                ],
                self::TEST_MODE_ERROR_MESSAGE
            );
        }
    }

}

