<?php

namespace RZP\Models\Customer;

use Illuminate\Support\Arr;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Request;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Customer\Account\Metrics\Metric;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Account;
use RZP\Models\Payout;
use RZP\Models\Address;
use RZP\Models\Payment;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Customer\Account\Constants as AccountConstants;
use RZP\Error\PublicErrorDescription;
use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->device = $this->app['basicauth']->getDevice();

        $this->core = new Customer\Core;
    }

    /**
     * Creates Local customer entity for merchant
     * @param  array $input
     * @return array customer data
     */
    public function createLocalCustomer($input)
    {
        $failOnDuplicate = true;

        if ((isset($input[Entity::FAIL_EXISTING])) and
            ($input[Entity::FAIL_EXISTING] === '0'))
        {
            $failOnDuplicate = false;
        }

        unset($input[Entity::FAIL_EXISTING]);

        $customer = $this->core->createLocalCustomer($input, $this->merchant, $failOnDuplicate);

        return $customer->toArrayPublic();
    }

    /**
     * Gets or Creates Local customer entity for merchant
     * @param array $input
     * @return array customer data
     */
    public function getOrCreateLocalCustomerInternal(array $input): array
    {
        if((isset($input['merchant_id']) === false) or (isset($input['contact']) === false)) {
            throw new Exception\BadRequestValidationFailureException('Merchant Id or Contact Id
            is not present in the request');
        }

        $failOnDuplicate = true;
        if ((isset($input[Entity::FAIL_EXISTING])) and
            ($input[Entity::FAIL_EXISTING] === '0'))
        {
            $failOnDuplicate = false;
        }

        unset($input[Entity::FAIL_EXISTING]);
        $merchantId = $input['merchant_id'];
        unset($input['merchant_id']);

        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $merchantId, ['methods', \RZP\Models\Merchant\Entity::GROUPS, \RZP\Models\Merchant\Entity::ADMINS]);
        $this->merchant = $merchant;

        try
        {
            $customer = $this->core->createLocalCustomer($input, $this->merchant, $failOnDuplicate);
        }
        catch (\Exception $e)
        {
            if($e->getMessage() === PublicErrorDescription::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS)
            {
                $this->trace->info(TraceCode::CUSTOMER_ALREADY_EXISTS,
                    [
                        'contact'   => $input['contact'],
                        'merchantId'=> $merchantId
                    ]);

                $customer = $this->core->getCustomerByContactAndMerchant($input['contact'], $this->merchant);
            }
            else
            {
                throw $e;
            }
        }

        return $customer->toArrayPublic();
    }

    /**
     * Creates Global customer entity for shared merchant
     * @param  array $input customer data
     * @return array customer data
     */
    public function createGlobalCustomer($input)
    {
        $customer = $this->core->createGlobalCustomer($input);

        return $customer->toArrayPublic();
    }

    /**
     * Fetches the both Global & Local Customer details for Checkout based on
     * app_token in session or customer_id.
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     */
    public function getCustomerDetailsForCheckout(array $input): array
    {
        $isGlobalCustomer = empty($input['customer_id']);

        if ($isGlobalCustomer) {
            $input[Payment\Entity::APP_TOKEN] = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

            $this->setCheckCookieInSession();
        }

        /** @var Entity $customer */
        $customer = null;

        $customerData = [
            'email' => '',
            'contact' =>  '',
            'is_global_customer' => $isGlobalCustomer,
            'has_saved_card_tokens' => false,
            'has_saved_addresses' => false,
        ];

        if (empty($input[Payment\Entity::APP_TOKEN]) && empty($input['customer_id'])) {
            if (!empty($input['contact']) && !empty($input['device_token'])) {
                $contact = Customer\Validator::validateAndParseContact($input['contact']);

                $customer = $this->repo->customer->findByContactAndMerchant($contact, $this->merchant);
            }
        } else {
            [$customer, $appToken] = $this->core->getCustomerAndApp($input, $this->merchant, $isGlobalCustomer);
        }

        if ($customer === null) {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CUSTOMER_ID);
        }

        $customerData['email'] = $customer->getEmail();
        $customerData['contact'] =  $customer->getContact();
        $customerData['is_global_customer'] = $customer->isGlobal();

        // This is required for subscription use-cases
        if ($customer->hasGlobalCustomer()) {
            $customerData[Entity::GLOBAL_CUSTOMER_ID] = $customer->getAttribute(Entity::GLOBAL_CUSTOMER_ID);
        }

        if ($appToken !== null &&
            Base\Utility::isUpdatedAndroidSdk($input) &&
            ($appToken->getMerchantId() === Account::SHARED_ACCOUNT)
        ) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,
                null,
                null,
                'We do not support sending customer data for first payment with new sdk'
            );
        }

        // This case comes when customer_id is sent in the input (always local customer).
        if ($customer->isLocal() === true) {
            $customerData['customer_id'] = $customer->getPublicId();
        }

        $customerTokensCount = $this->getCardTokensCountByCustomer($customer, $this->merchant);

        if ($customerTokensCount > 0) {
            $customerData['has_saved_card_tokens'] = true;
        }

        if ($this->merchant->isFeatureEnabled(Constants::ONE_CLICK_CHECKOUT)) {
            $rzpAddresses = $this->core->fetchRzpAddressesFor1CC($customer);

            $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);

            $thirdPartyAddresses = $this->core->fetchThirdPartyAddressesFor1cc($customer);

            $addresses = array_merge($rzpAddresses, $thirdPartyAddresses);

            $customerData['addresses'] = $addresses;

            if (count($addresses) > 0) {
                $customerData['has_saved_addresses'] = true;
            }

            $customerData['1cc_consent_banner_views'] = $addressConsentView;

            $customerData['1cc_customer_consent'] = $this->core->fetchCustomerConsentFor1CC(
                $customer->getContact(),
                $this->merchant->getId()
            );
        }

        return $customerData;
    }

    /**
     * Edits a local customer
     *
     * @param  string id of the customer
     * @param  array  edit params for customer
     * @return array  updated customer entity
     */
    public function edit($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $customer = $this->core->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    /**
     * @param $input
     * @return array
     * @throws BadRequestException|\Throwable
     */
    public function editGlobalCustomer($input): array
    {
        $merchantId = $this->merchant->getId();

        $this->trace->info(TraceCode::GLOBAL_CUSTOMER_EDIT_REQUEST, [
            'merchant_id' => $merchantId,
        ]);

        $this->trace->count(Metric::GLOBAL_CUSTOMER_EDIT_COUNT);

        try{
            Customer\Validator::validateEditGlobalCustomer($input);
        }
        catch(\Exception $exception)
        {
            // catching execption to log data
            $this->trace->traceException($exception, Trace::ERROR, TraceCode::GLOBAL_CUSTOMER_EDIT_INVALID_INPUT, [
                'merchant_id' => $merchantId,
            ]);

            $this->trace->count(Metric::GLOBAL_CUSTOMER_EDIT_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::GLOBAL_CUSTOMER_EDIT_INVALID_INPUT,
            ]);

            throw $exception;
        }

        $customer = $this->getCustomerFromSession();

        if ($customer === null)
        {
            $this->trace->error(TraceCode::GLOBAL_CUSTOMER_NOT_FOUND_IN_SESSION, [
                'merchant_id' => $merchantId,
            ]);

            $this->trace->count(Metric::GLOBAL_CUSTOMER_EDIT_ERROR, [
                Metric::LABEL_ERROR_MESSAGE => TraceCode::GLOBAL_CUSTOMER_NOT_FOUND_IN_SESSION,
            ]);

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED
            );
        }

        $this->core->edit($customer, [
            'email' => $input['email'],
        ]);

        return [];
    }

    /**
     * Fetch local customer using id
     *
     * @param  string id of the customer
     * @return array customer details
     */
    public function fetch($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        return $customer->toArrayPublic();
    }

    /**
     * Fetch local customer using customerId and merchantId
     *
     * @param  string $customerId
     * @param  string $merchantId
     * @return array customer details
     */
    public function fetchByCustomerAndMerchantId($customerId, $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFailPublicWithRelations(
            $merchantId, ['methods', \RZP\Models\Merchant\Entity::GROUPS, \RZP\Models\Merchant\Entity::ADMINS]);

        $this->merchant = $merchant;

        return $this->repo->customer->findByPublicIdAndMerchant($customerId, $this->merchant);
    }

    public function fetchByDeviceAuth()
    {
        return $this->device->customer->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $this->trace->info(TraceCode::CUSTOMER_FETCH,
                           [
                               'input'   => $input
                           ]);

        $customers = $this->repo->customer->fetch($input, $this->merchant->getId());

        return $customers->toArrayPublic();
    }

    /**
     * Delete a local customer
     *
     * @param  local customer id
     * @return deleted customer
     */
    public function delete($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $customer = $this->repo->customer->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    public function addBankAccount($id, $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $ba = (new BankAccount\Core)->addOrUpdateBankAccountForCustomer($input, $customer);

        return $ba->toArrayPublic();
    }

    public function getBankAccounts($id)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant($id, $this->merchant);

        $accounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        return $accounts->toArrayPublic();
    }

    public function deleteBankAccount($id, $baId)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        BankAccount\Entity::verifyIdAndStripSign($baId);

        $account = $this->repo->bank_account->getBankAccountByIdCustomerIdAndMerchantId($baId, $id, $this->merchant->getMerchantId());

        // if account does not exist
        if($account === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR_BANKING_ACCOUNT_NOT_FOUND,
                BankAccount\Entity::ENTITY_ID,
                [
                    'id' => $id
                ]);
        }

        // if bank account is already deleted throw an exception
        if($account->isDeleted() === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ACCOUNT_ALREADY_DELETED,
                BankAccount\Entity::ENTITY_ID,
                [
                    'id' => $id
                ]);
        }

        // soft delete
        $this->repo->bank_account->deleteOrFail($account);

        return ['success' => true];
    }

    public function getDeviceCustomer()
    {
        $customerId = $this->device->getCustomerId();

        $customer = $this->repo->customer->fetchWithVpasBankAcnts($customerId);

        return $customer->toArrayPublic();
    }

    /**
     * Send Otp to customer
     *
     * @param  details of customer for otp send
     * @return success/failure
     */
    public function sendOtp($input)
    {
        $data = $this->core->sendOtp($input, $this->merchant);

        return $data;
    }

    /**
     * @param  otp verification data
     * @return success with tokens or failure
     */
    public function verifyOtp($input)
    {
        $data = $this->core->verifyOtp($input, $this->merchant);

        return $data;
    }

    /**
     * Verifies the authentication status of a request id.
     *
     * @param $input
     * @return array
     */
    public function verifyTrueCallerAuthRequest($input): array
    {
        return $this->core->verifyTrueCallerAuthRequest($input, $this->merchant);
    }

    /**
     * Verifies the authentication status of a request id for 1cc.
     *
     * @param $input
     * @return array
     */
    public function verifyOneCCTrueCallerAuthRequest($input): array
    {
        return $this->core->verifyOneCCTrueCallerAuthRequest($input, $this->merchant);
    }

    /**
     * @param  otp verification data
     * @return success with tokens or failure
     * for 1cc
     */
    public function verifyOtp1cc($input)
    {
        $data = $this->core->verifyOtp1cc($input, $this->merchant);

        return $data;
    }

    /**
     * Support page - OTP verify
     *
     * @param  array $input
     * @return array
     */
    public function verifyOtpSupportPage(array $input): array
    {
        return $this->core->verifyOtpSupportPage($input);
    }

    /**
     * Used by the Open Wallet demo app
     */
    public function verifyOtpApp($input)
    {
        $data = $this->core->verifyOtpApp($input, $this->merchant);

        return $data->toArrayPublic();
    }

    public function fetchBankAccountsByContact($contact)
    {
        $contact = Customer\Validator::validateAndParseContact($contact);

        // TODO: Ensure that + is always entered in the database instead
        // of this hack

        if (strlen($contact) === 13)
        {
            $contact = substr($contact, 1);
        }

        $merchant = $this->repo->merchant->getSharedAccount();

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        return $this->repo->bank_account->getBankAccountsForCustomer($customer)->toArrayPublic();
    }

    /**
     * We do this only for Global customers. If the contact sent is not
     * of a global customer, we just return back without any tokens.
     *
     * @param      $contact
     * @param      $input
     * @param bool $sendOtp true when the flow is via checkout.
     *                      false when called from the preferences.
     *
     *                      For checkout, we decide to send the OTP
     *                      only if the customer has tokens. Else, we just return back.
     *                      For preferences, only if device token is present,
     *                      we search for tokens and return back the results.
     *
     * @return array global customer existence, send otp if true
     */
    public function fetchGlobalCustomerStatus($contact, $input, $sendOtp = false)
    {
        Customer\Validator::validateSmsHash($input);

        $data = ['saved' => false, 'saved_address' => false, '1cc_consent_banner_views' => 0];

        if ($sendOtp === true)
        {
            if (isset($input['provider']) === true)
            {
                $contact = Customer\Validator::validateAndParseContact($contact);

                $input['contact'] = $contact;

                $method = $input['method'] ?? Payment\Method::CARDLESS_EMI;

                $terminal = $this->repo
                                 ->terminal
                                 ->getByMerchantProviderAndMethod($input['provider'], $this->merchant['id'], $method);

                $gateway = Payment\Gateway::CARDLESS_EMI;

                switch ($method)
                {
                    case Payment\Method::PAYLATER:
                        $gateway = Payment\Gateway::PAYLATER;
                        break;
                }

                if($this->merchant->isFeatureEnabled(\RZP\Models\Feature\Constants::REDIRECT_TO_EARLYSALARY))
                {
                    $input['callbackUrl'] = $this->getCallbackUrl();
                }

                // merchant id is required to fetch details from cache
                $input['merchant_id']      = $this->merchant['id'];
                $input['merchant_website'] = $this->merchant['website'];
                $input['merchant_mcc']     = $this->merchant['category'];
                $input['merchant_name']    = $this->merchant['name'];
                $input['merchant_features'] = $this->merchant->getEnabledFeatures();


                $retData = $this->app['gateway']->call($gateway, 'check_account', $input, $this->mode, $terminal);

                unset($input['merchant_id']);
                unset($input['merchant_website']);
                unset($input['merchant_mcc']);
                unset($input['merchant_name']);
                unset($input['callbackUrl']);
                unset($input['merchant_features']);

                if ($retData != null)
                {
                    return $retData;
                }

                $otpInput = ['contact' => $contact];

                $customBranding = (new MerchantCore())->isOrgCustomBranding($this->merchant);

                if ($customBranding === true)
                {
                    $otpInput['org_id'] = $this->merchant->getOrgId();
                }

                if (isset($input['sms_hash']) === true)
                {
                    $otpInput = array_merge($otpInput, ['sms_hash' => $input['sms_hash']]);
                }

                if (isset($input['otp_reason']) === true)
                {
                    $otpInput = array_merge($otpInput, ['otp_reason' => $input['otp_reason']]);
                }

                if (Arr::has($input, ['otp_reason', 'merchant_domain']) === true &&
                    $this->isMWebOtpAutoReadOtpReason($input['otp_reason']))
                {
                    $otpInput = array_merge($otpInput, ['merchant_domain' => $input['merchant_domain']]);
                }

                $this->sendOtp($otpInput);

                return ['saved' => true];
            }

            $sessionData = optional($this->app['request']->getSession())->all();

            $this->trace->info(
                TraceCode::CUSTOMER_CHECKCOOKIE_STATUS,
                [
                    'session' => $sessionData,
                    'input'   => $input
                ]);

            $key = $this->mode . '_checkcookie';

            if (empty($sessionData[$key]) === true)
            {
                return $data;
            }
        }

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($contact);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        if ($customer !== null)
        {
            $data['saved'] = true;

            if (isset($input['device_token']))
            {
                $deviceToken = $input['device_token'];

                $result = $this->validateDeviceToken($deviceToken, $customer);

                if (($result['valid'] === true))
                {
                    $data['email'] = $customer->getEmail();

                    if (isset($result['tokens']))
                    {
                        $data['tokens'] = $result['tokens'];
                    }

                    $sendOtp = false;
                }
            }

            // check for saved addresses
            $rzpAddressCount = $this->repo->address->fetchRzpAddressCountFor1cc($customer);
            if ($rzpAddressCount !== 0)
            {
                $data['saved_address'] = true;
            }
            $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);
            $data['1cc_consent_banner_views'] = $addressConsentView;

            //fetch customer consent
            $data['1cc_customer_consent'] = $this->core->fetchCustomerConsentFor1CC($customer->getContact(), $this->merchant->getId());

            // we are introducing strict param for this use case: to check if customer has saved tokens even when we
            // dont send otp. this is required for truecaller feature.
            $strict = false;

            if (isset($input['strict']) === true)
            {
                $strict = ($input['strict'] === 'true' || $input['strict'] === true);
            }

            // Check tokens count only when the device token is not present or not valid.
            if ($sendOtp === true || ($sendOtp === false && $strict === true))
            {
                $customerTokensCount = $this->getCardTokensCountByCustomer($customer, $this->merchant);

                if ($customerTokensCount === 0)
                {
                    $sendOtp = false;

                    $data['saved'] = false;
                }
            }

            if ($sendOtp === true)
            {
                $otpInput = ['contact' => $contact];

                $customBranding = (new MerchantCore())->isOrgCustomBranding($this->merchant);

                if ($customBranding === true)
                {
                    $otpInput['org_id'] = $this->merchant->getOrgId();
                }

                if (isset($input['sms_hash']) === true)
                {
                    $otpInput = array_merge($otpInput, ['sms_hash' => $input['sms_hash']]);
                }

                if (isset($input['otp_reason']) === true)
                {
                    $otpInput = array_merge($otpInput, ['otp_reason' => $input['otp_reason']]);
                }

                if (Arr::has($input, ['otp_reason', 'merchant_domain']) === true &&
                    $this->isMWebOtpAutoReadOtpReason($input['otp_reason']))
                {
                    $otpInput = array_merge($otpInput, ['merchant_domain' => $input['merchant_domain']]);
                }

                $this->sendOtp($otpInput);
            }
        }

        return $data;
    }

    /**
     * Calculates count of all merchant saved card tokens associated to the customer
     *
     * @param Customer\Entity $customer
     * @param MerchantEntity $merchant
     * @return integer
     */
    public function getCardTokensCountByCustomer(Customer\Entity $customer, MerchantEntity $merchant): int
    {
        $tokenCore = (new Token\Core());

        $tokens = $tokenCore->fetchTokensByCustomerForCheckout($customer, $merchant);

        $tokens = $tokenCore->removeNonCardTokens($tokens);

        $tokens = $tokenCore->removeDisabledNetworkTokens($tokens, $merchant->methods->getCardNetworks());

        $tokens = $tokenCore->removeNonCompliantCardTokens($tokens);

        $tokens = $tokenCore->removeNonActiveTokenisedCardTokens($tokens);

        return count($tokens);
    }

    /**
     * Validates if device token is valid device token for a contact
     *
     * @param  string $deviceToken to be validated
     * @param         $customer
     *
     * @return array issues a new app_token if device_token is valid
     */
    public function validateDeviceToken($deviceToken, $customer)
    {
        $result = ['valid' => false];

        $apps = $this->repo->app_token->fetchAppsByDeviceToken(
            $customer,
            $deviceToken);

        if (($apps !== null) and ($apps->count() > 0))
        {
            $result['valid'] = true;
        }

        // If result is valid, then create a new app token.
        if ($result['valid'] === true)
        {
            $custAppInput = [
                AppToken\Entity::DEVICE_TOKEN  => $deviceToken
            ];

            $app = (new AppToken\Core)->create($custAppInput, $customer, $this->merchant);

            $this->core->putAppTokenInSession($app);

            $tokenCore = new Token\Core();

            // Fetch existing tokens if exists
            $tokens = $tokenCore->fetchTokensByCustomerForCheckout($customer, $this->merchant);

            $tokens = $tokenCore->removeNonCompliantCardTokens($tokens);

            $tokens = $tokenCore->removeNonActiveTokenisedCardTokens($tokens);

            if (($tokens !== null) and ($tokens->count() > 0))
            {
                $tokens = $tokenCore->addConsentFieldInTokens($tokens);

                $result['tokens'] = $tokens->toArrayPublic();
            }
        }

        return $result;
    }

    public function updateSmsStatus($gateway, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($gateway, $input);

        return $data;
    }

    public function fetchBalance($accountId)
    {
        $input = Request::all();

        Entity::stripSignWithoutValidation($accountId);
        $bankAccount = $this->repo->bank_account->findOrFail($accountId);

        if ($bankAccount->getEntityId() !== $this->device->customer->getId())
        {
            return;
        }

        $data = $this->core->sendBalanceEnqRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        // msg id
        // Cache the balance from the callback and make a cache call here?
        $balance = 10000;

        return $balance;
    }

    public function fetchBankAccount($accountId)
    {
        Entity::stripSignWithoutValidation($accountId);

        $bankAccount = $this->repo->bank_account->find($accountId);

        if ($bankAccount->getEntityId() !== $this->device->customer->getId())
        {
            return;
        }

        return $bankAccount->toArrayPublic();
    }

    public function fetchPaymentsForGlobalCustomer($input)
    {
        (new Validator())->validateInput('fetch_payments_for_global_customer', $input);

        $this->mode = $input['mode'] ?? Mode::LIVE;

        $this->auth->setModeAndDbConnection($this->mode);

        $customer = $this->getCustomerFromSession();

        if ($customer === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED
            );
        }

        $skip = $input['skip'] ?? AccountConstants::FETCH_PAYMENTS_DEFAULT_SKIP;

        $count = $input['count'] ?? AccountConstants::FETCH_PAYMENTS_DEFAULT_COUNT;

        return $this->core->fetchPaymentsByCustomerContact($customer, $skip, $count);
    }

    public function createGlobalAddress(array $input)
    {
        $address = $this->core->createGlobalAddress($input);

        return $address;
    }

    public function editGlobalAddress(array $input)
    {
        return $this->core->editGlobalAddress($input);
    }

    /**
     * @throws BadRequestException
     */
    public function recordAddressConsent1cc($input): array
    {
        return $this->core->recordAddressConsent1cc($input);
    }

    public function recordAddressConsent1ccAudits(array $input): array
    {
        (new Address\Core)->recordAddressConsent1ccAudits($input);

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($input['contact']);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);

        return [
            '1cc_consent_banner_views' => $addressConsentView
        ];
    }

    public function createAddress($customerId, array $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant(
                                            $customerId, $this->merchant);

        $address = (new Address\Core)->create($customer, Address\Type::CUSTOMER, $input);

        return $address->toArrayPublic();
    }

    public function fetchAddresses($customerId, array $input)
    {
        Entity::verifyIdAndStripSign($customerId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);

        $addresses = $this->repo->address->fetchAddressesForEntity($customer, $input);

        return $addresses->toArrayPublic();
    }

    public function setPrimaryAddress($customerId, $addressId)
    {
        $address = $this->getAddressFromCustomerId($customerId, $addressId);

        // If the address is already set as primary, there's nothing to do.
        if ($address->isPrimary() === true)
        {
            return $address;
        }

        $address = (new Address\Core)->setPrimaryAddress($address);

        return $address->toArrayPublic();
    }

    public function deleteAddress($customerId, $addressId)
    {
        $address = $this->getAddressFromCustomerId($customerId, $addressId);

        $address = (new Address\Core)->delete($address);

        if ($address === null)
        {
            return [];
        }

        return $address->toArrayPublic();
    }

    /**
     * Gets the customer from customerId, with merchant as the restriction
     * Gets the address from addressId, with customer as the restriction
     * This ensures that the merchant is retrieving his customer only
     * and is attempting to get an address of that customer only.
     *
     * @param $customerId
     * @param $addressId
     * @return Address\Entity
     */
    protected function getAddressFromCustomerId($customerId, $addressId)
    {
        Entity::verifyIdAndStripSign($customerId);

        Address\Entity::verifyIdAndStripSign($addressId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);

        return $this->repo->address->findByEntityAndId($addressId, $customer);
    }

    public function setMPINForBankAccounts($accountNumber, $creds)
    {
        $bankAccounts = $this->repo->bank_account->getBankAccountsFromAccountNumber($accountNumber);

        $success = false;
        $error = [];

        if (isset($creds['otp']))
        {
            if ($creds['otp'] === '123456')
            {
                foreach ($bankAccounts as $bankAccount)
                {
                    $last6 = substr($bankAccount->getAccountNumber(), -6);

                    if ($creds['expiry'] === '1224')
                    {
                        $bankAccount->setMpin($creds['mpin']);
                        $this->repo->saveOrFail($bankAccount);

                        $success = true;
                    }
                    else
                    {
                        $error[] = 'Invalid Expiry';
                    }
                }
            }
            else
            {
                $error[] = 'Invalid OTP';
            }
        }
        else if (isset($creds['nmpin']))
        {
            foreach ($bankAccounts as $bankAccount)
            {
                if ($bankAccount->getMpin() === $creds['mpin'])
                {
                    $bankAccount->setMpin($creds['nmpin']);
                    $this->repo->saveOrFail($bankAccount);

                    $success = true;
                }
            }
        }

        if (!$success and empty($error))
        {
            $error[] = 'Invalid MPIN';
        }

        return [$success, $error];
    }

    public function setMpin($bankAccountId, $input)
    {
        Entity::stripSignWithoutValidation($bankAccountId);

        $bankAccount = $this->repo->bank_account->find($bankAccountId);

        // Confirm ownership of bank account
        assertTrue($bankAccount->getEntityId() === $this->device->customer->getId());

        $otpResponse = $this->core->sendOtpRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        // TODO: Conditionally send Mpin request if otp request acknowledgement received correctly
        $response = $this->core->sendSetMpinRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        return $response;
    }

    public function resetMpin($bankAccountId, $input)
    {
        Entity::stripSignWithoutValidation($bankAccountId);

        $bankAccount = $this->repo->bank_account->find($bankAccountId);

        // Confirm ownership of bank account
        assertTrue($bankAccount->getEntityId() === $this->device->customer->getId());
        $response = $this->core->sendResetMpinRequestToGateway($this->device, $this->device->customer, $bankAccount, $input);

        return $response;
    }

    public function fetchUpiBankAccounts($ifsc = 'RAZR')
    {
        $accounts = $this->repo->bank_account->getBankAccountsForCustomer($this->device->customer, $ifsc);

        return $accounts->toArrayPublic();
    }

    /**
     * Fetch balance details for a customer wallet account
     *
     * @param  string $customerId
     * @return array
     */
    public function getCustomerBalance(string $customerId) : array
    {
        Entity::verifyIdAndStripSign($customerId);

        $customerBalance = $this->repo
                                ->customer_balance
                                ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        // If no customer balance entity exists, return a default empty entity
        if ($customerBalance === null)
        {
            $customerBalance = (new Customer\Balance\Entity)->build();
        }

        return $customerBalance->toArrayPublic();
    }

    public function getCustomerBalanceStatement(string $customerId, array $input = []) : array
    {
        Entity::verifyIdAndStripSign($customerId);

        $customerBalance = $this->repo
                                ->customer_balance
                                ->findByCustomerIdAndMerchantSilent($customerId, $this->merchant);

        // If no customer balance entity exists, return an empty collection
        $records = new Base\PublicCollection;

        if ($customerBalance !== null)
        {
            $records = (new Customer\Transaction\Core)->getStatement($customerBalance, $this->merchant, $input);
        }

        return $records->toArrayPublic();
    }

    public function processCustomerWalletPayout(string $customerId, array $input = []): array
    {
        Entity::verifyIdAndStripSign($customerId);

        /** @var Customer\Balance\Entity $customerBalance */
        $customerBalance = $this->repo->customer_balance->findByIdAndMerchant($customerId, $this->merchant);

        $customer = $customerBalance->customer;

        $payout = (new Payout\Core)->createPayoutFromCustomerWallet($input, $customer, $this->merchant);

        return $payout->toArrayPublic();
    }

    public function fetchTokensForGlobalCustomerV2() : array
    {
        $customer = $this->getCustomerFromSession();

        if ($customer === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED
            );
        }

        $this->trace->info(TraceCode::CUSTOMER_FETCH, [
            Customer\Token\Entity::CUSTOMER_ID => $customer->getId(),
        ]);

        $fetchedTokenDetails = (new Token\Core())->fetchTokenDetailsForCustomer($customer);

        return $fetchedTokenDetails;
    }

    public function deleteTokenForGlobalCustomerV2(array $input) : array
    {
        $customer = $this->getCustomerFromSession();

        if ($customer === null)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED
            );
        }

        $customerId = $customer->getId();

        $this->trace->info(TraceCode::CUSTOMER_FETCH, [
            Customer\Token\Entity::CUSTOMER_ID => $customerId
        ]);

        $deletedTokens = (new Token\Core())->deleteTokensForCustomer($input, $customerId);

        return $deletedTokens;
    }

    protected function getCustomerFromSession() : ?Entity
    {
        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        if ($appTokenId === null)
        {
            return null;
        }

        $app = (new AppToken\Core)->getAppByAppTokenId($appTokenId, $this->repo->merchant->getSharedAccount());

        if ($app === null) {
            return null;
        }

        return $this->repo->customer->fetchByAppToken($app);
    }

    /**
     * Gets global customer from db or create one.
     * Internal route for 1cc micro service.
     *
     * @param $input
     *
     * @return Entity $customer
     */
    public function getOrCreateGlobalCustomer1cc(array $input): array
    {
        (new Validator)->setStrictFalse()->validateInput('createGlobalCustomer1cc', $input);

        $response = $this->core->getOrCreateGlobalCustomer1cc($input);

        return $response;
    }

    /**
     * Fetch global customer by public ID
     * Internal route for 1cc micro service.
     *
     * @param $input
     *
     * @return Entity $customer
     */
    public function fetchGlobalCustomerByID(string $id): array
    {
        $customerArr = $this->core->fetchGlobalCustomerByID($id);

        return $customerArr;
    }

    /**
     * @throws BadRequestException
     */
    public function recordCustomerConsent1cc($input)
    {
        return $this->core->recordCustomerConsent1cc($input);
    }

    /**
     * We set `<mode>_checkcookie` key in the session in preferences request
     * so that in subsequent calls we can identify if the browser has cookies
     * enabled & accordingly we provide saved tokens/flash-checkout functionality.
     *
     * NOTE: This is a legacy but inefficient solution. We are unnecessarily
     * creating sessions & storing them in redis in preferences even for guest
     * checkout (where customer doesn't log in) which has a share of >50% of
     * all std. checkout sessions where this session will never be of any use.
     *
     * @return void
     */
    protected function setCheckCookieInSession(): void
    {
        // Other checks like pinging card-vault are done in parallel in checkout-service
        if (!$this->merchant->isFeatureEnabled(Constants::NOFLASHCHECKOUT)) {
            $key = $this->mode . '_checkcookie';

            $session = null;

            try {
                $session = optional($this->app['request']->session());
            } catch (\Exception $ex) {
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::NO_SESSION_FOUND_EXCEPTION, []);
            }

            // If Laravel wasn't able to start/retrieve session due to Redis infra failure then return
            if ($session === null) {
                return;
            }

            $session->put($key, '1');
        }
    }

    private function isMWebOtpAutoReadOtpReason(string $otpReason): bool
    {
        if ($otpReason === 'mweb_save_card')
        {
            return true;
        }

        if ($otpReason === 'mweb_access_card')
        {
            return true;
        }

        return false;
    }
}
