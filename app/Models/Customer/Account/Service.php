<?php

namespace RZP\Models\Customer;

use Illuminate\Support\Arr;
use RZP\Base\ConnectionType;
use RZP\Constants\Mode;
use RZP\Constants\Tracing;
use RZP\Error\ErrorCode;
use RZP\Exception;
use Request;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\ServerErrorException;
use RZP\Http\Edge\PassportUtil;
use RZP\Http\RequestContextV2;
use RZP\Models\Base;
use RZP\Models\Customer\Account\Metrics\Metric;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\Account;
use RZP\Models\Merchant\OneClickCheckout\MigrationUtils\SplitzExperimentEvaluator;
use RZP\Models\Merchant\OneClickCheckout\Utils\CommonUtils;
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
use RZP\Models\Merchant\OneClickCheckout\MagicCheckoutService;

class Service extends Base\Service
{
    protected RequestContextV2 $reqCtx;

    public function __construct()
    {
        parent::__construct();

        $this->device = $this->app['basicauth']->getDevice();

        $this->core = new Customer\Core;

        $this->reqCtx = $this->app['request.ctx.v2'];
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

        //findOrFailPublic is fetching merchant from asv, so using that function and setting the relations explicitly.
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        $merchant->load('methods');
        $merchant->load(\RZP\Models\Merchant\Entity::GROUPS);
        $merchant->load(\RZP\Models\Merchant\Entity::ADMINS);

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
     * Performs the following functions:
     * 1. Finds existing global customer using input
     * 2. Creates a global customer if one doesn't exist
     * 3. Fetches saved instruments (cards + vpa)
     * 4. Saves address consent for 1cc (1cc/otp/verify)
     * 5. Fetches saved addresses (RZP + 3'rd party), consent_banner_views,
     *    customer_consent for 1cc
     *
     * This method is used by new otp_verify_v2 route in checkout-service after
     * a successful otp verification to fetch global customer details (or)
     * create a global customer if it doesn't exist using contact & email.
     *
     * @param array $input
     *
     * @return array
     *
     * @throws BadRequestException
     */
    public function findOrCreateGlobalCustomerForCheckout(array $input): array
    {
        $isOneCC = (bool) ($input['is_one_cc'] ?? false);
        unset($input['is_one_cc']);

        // This will be used to decide whether FE has to show the addresses sorted by latest usage
        $shouldSortAddresses = (bool) ($input['one_cc_sort_addresses'] ?? false);
        unset($input['one_cc_sort_addresses']);

        (new Validator())->validateInput('global_customer_create', $input);

        // Parse contact
        $input = Customer\Validator::validateAndParseContactInInput($input);

        // Get global customer from db or create one.
        $customer = $this->core->getOrCreateGlobalCustomer($input);

        $response = [
            'customer' => [
                Entity::ID => $customer->getId(),
                Entity::EMAIL => $customer->getEmail(),
                Entity::CONTACT => $customer->getContact(),
            ],
        ];

        // Doing this temporarily till we ramp up otp_verify_v2 to 100%.
        // This should be removed post that.
        $appToken = $this->core->createCustomerAppToken($customer, $input, $this->merchant);
        $this->core->putAppTokenInSession($appToken);
        if ($this->core->isCookieDisabledOnBrowser() === true) {
            $response['session_id'] = $this->core->getTemporarySessionToken();
        }
        // Set check_cookie to ensure other flows don't break
        $this->setCheckCookieInSession();

        $tokenCore = (new Token\Core());

        // Fetch existing tokens for global customer
        $tokens = $tokenCore->fetchTokensByCustomerForCheckout($customer, $this->merchant);

        if ($tokens->isNotEmpty()) {
            $tokens = $tokenCore->filterTokensForCheckout($tokens);

            $tokens = $tokens->toArrayPublic();

            // sending notes and card flows as empty object for empty values
            // without this, php sends them as empty arrays
            foreach ($tokens['items'] as $i => $token) {
                if (isset($token['card'])) {
                    $tokens['items'][$i]['card']['flows'] = (object)($token['card']['flows'] ?? []);
                }
                $tokens['items'][$i]['notes'] = (object) ($token['notes'] ?? []);
            }

            $response['tokens'] = $tokens;
        }

        if ($isOneCC === true) {
            // ToDo: This if block should move to magic-checkout-service
            if (Arr::has($input, 'address_consent.device_id')) {
                $addressConsentInput = [
                    'device_id' => $input['address_consent']['device_id'],
                ];

                (new Address\Core)->recordAddressConsent1cc($addressConsentInput, $customer);
            }
            $addressSortType = (new CommonUtils())->canRouteToCheckoutServiceForAddressSorting($customer['id']) ?
                \RZP\Models\Merchant\Merchant1ccConfig\Constants::ONE_CC_ADDRESS_SORT_OTHER :
                \RZP\Models\Merchant\Merchant1ccConfig\Constants::ONE_CC_ADDRESS_SORT_LAST_UPDATED;

            $rzpAddresses = $this->core->fetchRzpAddressesFor1CC($customer,$addressSortType);
            $thirdPartyAddresses = $this->core->fetchThirdPartyAddressesFor1cc($customer);
            $addresses = array_merge($rzpAddresses, $thirdPartyAddresses);

            $response['one_cc_address_sort_by'] = $addressSortType;
            $response['one_cc_addresses'] = $addresses;
            $response['one_cc_consent_banner_views'] = $this->core->fetchAddressConsentViewsFor1CC($customer);

            if ((new SplitzExperimentEvaluator())->useTripleConsentForMerchant($this->merchant->getId()))
            {
                $oneCCTripleConsent= $this->core->fetchTripleConsentFor1CC($customer->getContact(), $this->merchant->getId());
                $response['one_cc_email_customer_consent'] = $oneCCTripleConsent['one_cc_email_customer_consent'];
                $response['one_cc_whatsapp_customer_consent'] = $oneCCTripleConsent['one_cc_whatsapp_customer_consent'];
                $response['one_cc_customer_consent'] = $oneCCTripleConsent['status'];
            }
            else
            {
                $response['one_cc_customer_consent'] = $this->core->fetchCustomerConsentFor1CC(
                    $customer->getContact(),
                    $this->merchant->getId(),
                );
            }
        }

        if($this->merchant->isLRSTravelCitiFlowEnabled())
        {
            $addresses = $this->repo->address->fetchAddressesForEntity($customer, ["type" => 'billing_address']);

            $response['addresses'] = $addresses;
        }

        return $response;
    }

    public function internalFetchGlobalAddressesFor1CCCustomer(array $input): array
    {
        $contact = $input['contact'] ?? null;
        if ($contact != null)
        {
            $customer = $this->core->getGlobalCustomerByContact("+91" . $contact);
        }
        else
        {
            $customer = $this->core->fetchGlobalCustomerEntityByID($input['customer_id']);
        }
        $rzpAddresses = $this->core->fetchRzpAddressesFor1CCInternal($customer);
        $thirdPartyAddresses = $this->core->fetchThirdPartyAddressesFor1cc($customer);
        return array_merge($rzpAddresses, $thirdPartyAddresses);
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
        $contact = null;

        $customerData = [
            'email' => '',
            'contact' =>  '',
            'is_global_customer' => $isGlobalCustomer,
            'has_saved_card_tokens' => false,
            'has_saved_addresses' => false,
        ];

        if (empty($input[Payment\Entity::APP_TOKEN]) && empty($input['customer_id'])) {
            $globalCustomerId = optional($this->reqCtx->passportUtil)->getGlobalCustomerId() ?: '';

            if ($globalCustomerId !== '') {
                $input[Payment\Entity::GLOBAL_CUSTOMER_ID] = $globalCustomerId;
            } elseif(!empty($input['contact']) && !empty($input['device_token'])) {
                $contact = Customer\Validator::validateAndParseContact($input['contact']);
            }
        }

        if (!empty($contact)) {
            $customer = $this->repo->customer->findByContactAndMerchant($contact, $this->merchant);
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

            $shouldSortAddresses = (bool) ($input['one_cc_sort_addresses'] ?? false);
            unset($input['one_cc_sort_addresses']);

            $addressSortType = (new CommonUtils())->canRouteToCheckoutServiceForAddressSorting($customer['id']) ?
                \RZP\Models\Merchant\Merchant1ccConfig\Constants::ONE_CC_ADDRESS_SORT_OTHER :
                \RZP\Models\Merchant\Merchant1ccConfig\Constants::ONE_CC_ADDRESS_SORT_LAST_UPDATED;


            $rzpAddresses = $this->core->fetchRzpAddressesFor1CC($customer, $addressSortType);

            $addressConsentView = $this->core->fetchAddressConsentViewsFor1CC($customer);

            $thirdPartyAddresses = $this->core->fetchThirdPartyAddressesFor1cc($customer);

            $addresses = array_merge($rzpAddresses, $thirdPartyAddresses);

            $customerData['addresses'] = $addresses;
            $customerData['one_cc_address_sort_by'] = $addressSortType;
            if (count($addresses) > 0) {
                $customerData['has_saved_addresses'] = true;
            }

            $customerData['1cc_consent_banner_views'] = $addressConsentView;

            $shouldUseTripleConsent = (new SplitzExperimentEvaluator())->useTripleConsentForMerchant($this->merchant->getId());
            if ($shouldUseTripleConsent) {
                $oneCCTripleConsent = $this->core->fetchTripleConsentFor1CC($customer->getContact(), $this->merchant->getId());
                $customerData['one_cc_email_customer_consent'] = $oneCCTripleConsent['one_cc_email_customer_consent'];
                $customerData['one_cc_whatsapp_customer_consent'] = $oneCCTripleConsent['one_cc_whatsapp_customer_consent'];
                $customerData['1cc_customer_consent'] = $oneCCTripleConsent['status'];

                return $customerData;
            }

            $customerData['1cc_customer_consent'] = $this->core->fetchCustomerConsentFor1CC(
                $customer->getContact(),
                $this->merchant->getId()
            );
        }

        if($this->merchant->isLRSTravelCitiFlowEnabled())
        {
            $addresses = $this->repo->address->fetchAddressesForEntity($customer, ["type" => 'billing_address']);

            $customerData['addresses'] = $addresses;
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
        //findOrFailPublic is fetching merchant from asv, so using that function and setting the relations explicitly.
        $merchant = $this->repo->merchant->findOrFailPublic($merchantId);
        $merchant->load('methods');
        $merchant->load(\RZP\Models\Merchant\Entity::GROUPS);
        $merchant->load(\RZP\Models\Merchant\Entity::ADMINS);

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
     * This route is added as part of customer session decomposition as customer
     * session create responsibility moved to checkout-service & global customer
     * identification responsibility moved to edge.
     *
     * @param array $input
     *
     * @return array
     *
     * @throws BadRequestException
     * @throws BadRequestValidationFailureException
     * @throws ServerErrorException
     */
    public function verifyTruecallerAuthRequestInternal($input): array
    {
        $isOneCc = (bool) ($input['is_one_cc'] ?? false);
        unset($input['is_one_cc']);

        if ($isOneCc) {
            $response = $this->core->verifyOneCCTruecallerAuthRequest($input, $this->merchant, true);
            if (array_key_exists('1cc_consent_banner_views', $response)) {
                $response['one_cc_consent_banner_views'] = $response['1cc_consent_banner_views'];
                unset($response['1cc_consent_banner_views']);
            }
            if (array_key_exists('1cc_customer_consent', $response)) {
                $response['one_cc_customer_consent'] = $response['1cc_customer_consent'];
                unset($response['1cc_customer_consent']);
            }
        } else {
            $response = $this->core->verifyTruecallerAuthRequest($input, $this->merchant, true);
        }

        if (!empty($response['tokens']['items'])) {
            $tokens = $response['tokens'];

            // sending notes and card flows as empty object for empty values
            // without this, php sends them as empty arrays
            foreach ($tokens['items'] as $i => $token) {
                if (isset($token['card'])) {
                    $tokens['items'][$i]['card']['flows'] = (object) ($token['card']['flows'] ?? []);
                }
                $tokens['items'][$i]['notes'] = (object)($token['notes'] ?? []);
            }

            $response['tokens'] = $tokens;
        }

        return $response;
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
     * @throws \Exception
     */
    public function internalSendOtp($input)
    {
        // Set Merchant basic auth
        $merchantId = $input['merchant_id'];

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);

        return $this->core->sendOtp($input, $this->merchant);
    }

    public function internalVerify1CCOtp($input)
    {
        // Set Merchant basic auth
        $merchantId = $input['merchant_id'];

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);
        unset($input["merchant_id"]);
        return $this->core->internalVerifyOtp1cc($input, $this->merchant);
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
    public function fetchGlobalCustomerStatus($contact, $input, $sendOtp = false, bool $isOneCc = false)
    {
        Customer\Validator::validateSmsHash($input);

        $data = [
            'saved' => false,
            'saved_address' => false,
            '1cc_consent_banner_views' => 0,
            'saved_cards_count' => 0,
            'saved_addresses_count' => 0,
            'saved_vpa_count' => 0
        ];

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

            // @ToDo: Remove this & all such checks when we stop using Laravel session for customers
            $sessionData = optional($this->app['request']->getSession())->all();

            $this->trace->info(
                TraceCode::CUSTOMER_CHECKCOOKIE_STATUS,
                [
                    'session' => $sessionData,
                    'input'   => $input
                ]);

            $key = $this->mode . '_checkcookie';

            // Don't skip sending OTPs for Otp Verify V2 Flows as we now support
            // logged in flows for browsers without cookies enabled.
            $isOtpVerifyV2Flow = !empty($input['otp_verify_v2']) && $input['otp_verify_v2'] !== 'false';

            if (!$isOtpVerifyV2Flow && empty($sessionData[$key]) === true)
            {
                return $data;
            }
        }

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($contact);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        $isExperimentEnabled = (new CommonUtils())->isExternalCustomerAddressExperimentEnabled();

        if($isOneCc && $isExperimentEnabled)
        {
            if ($customer === null)
            {
                $this->createGlobalCustomer(['contact' => $contact]);

                $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);
            }

            $addressCount = $this->repo->address->fetchRzpAddressCountFor1cc($customer);

            if ($addressCount === 0)
            {
                try
                {
                    $response = (new MagicCheckoutService\Service())->fetchCustomerAddress($contact);

                    $externalAddressCount = sizeof($response['addresses']);

                    if ($externalAddressCount > 0) {
                        $this->saveUnicommerceAddress($customer->getId(), $customer->getContact(), $response['addresses']);
                    }

                    $this->trace->count(Metric::THIRD_PARTY_ADDRESS_COUNT, [
                        'source' => 'unicommerce_turbo',
                        'address_count' => $externalAddressCount,
                    ]);
                } catch (\Throwable $e)
                {
                    $this->trace->error(TraceCode::UNICOMMERC_CUSTOMER_ADDRESS_API_ERROR, [
                        'error' => $e->getMessage(),
                    ]);

                    $this->trace->count(Metric::THIRD_PARTY_ADDRESS_API_ERROR_COUNT,[
                        'source' => 'unicommerce_turbo',
                    ]);
                }
            }
        }

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
            $data['saved_addresses_count'] = $rzpAddressCount;

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
                if($input['otp_reason']== AccountConstants::OTP_REASON_ACCESS_SAVED_WALLETS){
                    $customerTokensCount=$this->getWalletTokensCountByCustomer($customer, $this->merchant);
                }
                else if($input['otp_reason'] == AccountConstants::OTP_REASON_ACCESS_SAVED_VPAS){
                    $customerTokensCount = $this->getUpiTokensCountByCustomer($customer, $this->merchant);
                    $data['saved_vpa_count'] = $customerTokensCount;
                }
                else{
                    $customerTokensCount = $this->getCardTokensCountByCustomer($customer, $this->merchant);
                    $data['saved_cards_count'] = $customerTokensCount;
                }

                // If there are no card/vpa/wallet tokens , make saved as false and do not send otp
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

        $filters = [];

        if (!empty($input['payment_status'])) {
            $filters['payment_status'] = $input['payment_status'];
        }
        if (!empty($input['claim_status'])) {
            $filters['claim_status'] = $input['claim_status'];
        }
        if (!empty($input['insurance_status'])) {
            $filters['insurance_status'] = $input['insurance_status'];
        }

        return $this->core->fetchPaymentsByCustomerContact($customer, $skip, $count, $filters);
    }

    public function createGlobalAddress(array $input, string $customerId = '')
    {
        $address = $this->core->createGlobalAddress($input, $customerId);

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

    public function editLocalAddress($customerId, array $input)
    {
        $customer = $this->repo->customer->findByPublicIdAndMerchant(
                                            $customerId, $this->merchant);
        try
        {
            $address = $this->repo->address->findByEntityAndId($input[Base\UniqueIdEntity::ID], $customer);
        }
        catch (\Throwable $ex)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,null,null,PublicErrorDescription::BAD_REQUEST_CUSTOMER_ADDRESS_NOT_FOUND);
        }
                                    
        $address = (new Address\Core)->edit($address, $input);

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

        $globalCustomerId = optional($this->reqCtx->passportUtil)->getGlobalCustomerId() ?: '';

        if ($globalCustomerId !== '') {
            return $this->repo->customer->findByIdAndMerchantId(
                $globalCustomerId,
                Account::SHARED_ACCOUNT,
                ConnectionType::SLAVE,
            );
        }

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
        $validReasons = [
            'mweb_save_card',
            'mweb_access_card',
            'verify_coupon_v7',
            'mandatory_login_v7',
            'access_address_v7',
            'save_address_v7',
            'access_card_v7',
            'save_card_v7',
            'access_address_v8',
            'access_card_v8',
            'access_address_v9',
            'save_address_v9',
            'save_card_v9',
        ];

        return in_array($otpReason, $validReasons, true);
    }


    /**
     * Calculates count of all merchant saved wallet tokens associated to the customer
     *
     * @param Customer\Entity $customer
     * @param MerchantEntity $merchant
     * @return integer
     */
    public function getWalletTokensCountByCustomer(Customer\Entity $customer, MerchantEntity $merchant): int
    {
        $tokenCore = (new Token\Core());

        $tokens = $tokenCore->fetchTokensByCustomerForCheckout($customer, $merchant);

        $tokens = $tokenCore->removeNonWalletTokens($tokens);
        $tokens = $tokenCore->removeExpiredTokens($tokens);

        return count($tokens);
    }

    public function getMagicCustomer()
    {
        $globalCustomerId = optional($this->reqCtx->passportUtil)->getGlobalCustomerId() ?: '';

        if (empty($globalCustomerId) && session()->has($this->mode . '_app_token') === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $appToken = session()->get($this->mode . '_app_token');

        list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp(
            ['app_token' => $appToken, Payment\Entity::GLOBAL_CUSTOMER_ID => $globalCustomerId],
            $this->merchant,
            true);

        return $customer;
    }

    public function fetchCustomerConsent1cc($input)
    {
        $contact = $input['contact'];
        $merchantID = $input['merchant_id'];

        if(strlen($contact) == 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_CUSTOMER_CONTACT_REQUIRED);
        }

        if(strlen($merchantID) == 0)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_PRESENT);
        }
        if ((new SplitzExperimentEvaluator())->useTripleConsentForMerchant($merchantID))
        {
            return $this->core->fetchTripleConsentFor1CC($contact, $merchantID);
        }
        $customerConsent = $this->core->fetchCustomerConsentFor1CC($contact, $merchantID);
        $result['status'] = $customerConsent;

        return $result;
    }

    /**
     * Calculates count of all merchant saved upi tokens associated to the customer
     *
     * @param Customer\Entity $customer
     * @param MerchantEntity $merchant
     * @return integer
     */
    public function getUpiTokensCountByCustomer(Customer\Entity $customer, MerchantEntity $merchant): int
    {
        $tokenCore = (new Token\Core());

        $tokens = $tokenCore->fetchTokensByCustomerForCheckout($customer, $merchant);

        $tokens = $tokenCore->removeNonUpiTokens($tokens);

        return count($tokens);
    }

    private function saveUnicommerceAddress($customerId, $contact, $addressList)
    {
        $externalAddressList = [];
        foreach($addressList as $address){
            $globalAddress = [
                'contact' => $contact,
                'shipping_address' => [
                    'name' => $address['name'] ?? '',
                    'contact' => $contact,
                    'type' => 'shipping_address' ?? '',
                    'line1' => $address['line1'] ?? '',
                    'line2' => $address['line2'] ?? '',
                    'city' => $address['city'] ?? '',
                    'zipcode' => $address['zipcode'] ?? '',
                    'state' => $address['state'] ?? '',
                    'country' => $address['country'] ?? '',
                    'source_type' => 'unicommerce_turbo'
                ],
            ];
            $externalAddress = $this->createGlobalAddress($globalAddress, $customerId);
            array_push($externalAddressList,$externalAddress);
        }

        return $externalAddressList;

    }

    private function saveGlobalAddresses($customerId, $contact, array $addressList)
    {
        $externalAddressList = [];
        foreach ($addressList as $address) {
            $shippingAddress = [
                'name' => $address['name'] ?? '',
                'contact' => $contact,
                'type' => $address['type'] ?? 'shipping_address',
                'line1' => $address['line1'] ?? '',
                'line2' => $address['line2'] ?? '',
                'city' => $address['city'] ?? '',
                'zipcode' => $address['zipcode'] ?? '',
                'state' => $address['state'] ?? '',
                'country' => $address['country'] ?? '',
                'source_type' => 'unicommerce_turbo'
            ];

            $globalAddress = [
                'contact' => $contact,
                'shipping_address' => $shippingAddress,
            ];
            $externalAddress = $this->core->createGlobalAddress($globalAddress, $customerId, $this->merchant->getId());

            if (isset($externalAddress['shipping_address']))
            {
                array_push($externalAddressList, $externalAddress['shipping_address']);
            }
        }

        return $externalAddressList;

    }

    public function createGlobalCustomerAndAddress(array $input){

        (new Validator())->setStrictFalse()->validateInput('create_global_customer_and_address', $input);

        // Set Merchant basic auth
        $merchantId = $input['merchant_id'];

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->app['basicauth']->setMerchant($this->merchant);

        $createCustomerRequest = [
            'contact' => $input['contact'],
            'email'  => $input['email']
        ];

        $customer = $this->core->createGlobalCustomer($createCustomerRequest, false);

        $savedAddress = [];

        if(isset($input['addresses']) === true)
        {
            $savedAddress = $this->saveGlobalAddresses($customer->getId(), $customer->getContact(), $input['addresses']);
        }

        return [
            'contact' => $input['contact'],
            'email' => $input['email'],
            'customer_id' => $customer['id'],
            'addresses' => $savedAddress
        ];
    }

    // fetchAddressesForCustomer allows services to fetch all addresses of a customer based on diff
    // atrributes like contact, customer_id, etc.
    public function fetchAddressesForCustomer(array $input) : array
    {
        if (empty($input['contact']) == false)
        {
            $contact = $input['contact'];
            $addresses = (new Address\Core)->fetchAddressesForContact($contact);
            return $addresses->toArrayPublic();
        }
        // NOTE: Add additional query param support here.
        return [];
    }

    // fetchAddressesForCustomerInternal is to be used by microservices to fetch additional fields
    // not in `public()` for further processing.
    public function fetchAddressesForCustomerInternal(array $input) : array
    {
        if (empty($input['contact']) == false)
        {
            $contact = $input['contact'];
            $addresses = (new Address\Core)->fetchAddressesForContact($contact);
            $customerAddresses = [];
            foreach ($addresses as $address)
            {
                array_push($customerAddresses, $address->toArray());
            }
            return ["addresses" => $customerAddresses];
        }
        return [];
    }
}
