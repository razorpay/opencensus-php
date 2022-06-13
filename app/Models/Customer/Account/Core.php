<?php

namespace RZP\Models\Customer;

use http\Url;
use RZP\Constants\Mode;
use Razorpay\Trace\Logger as Trace;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Base;
use RZP\Models\Terminal;
use RZP\Models\Customer;
use RZP\Models\Address;
use RZP\Models\Device;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Customer\Account\Constants as AccountConstants;
use RZP\Constants;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Payment;
use RZP\Models\BankAccount;
use RZP\Models\Upi;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Locale\Core as Locale;

class Core extends Base\Core
{
    // In seconds (Multiplying by 60 since put() takes an argument in secs)
    const TEMPORARY_SESSION_TIME = 10 * 60;

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param bool            $failOnDuplicate
     *
     * @return Entity
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function createLocalCustomer(array $input, Merchant\Entity $merchant, $failOnDuplicate = true)
    {
        return $this->create($input, $merchant, $failOnDuplicate);
    }

    /**
     * @param      $input
     * @param bool $failOnDuplicate
     *
     * @return Entity
     * @throws Exception\LogicException
     * @throws Exception\BadRequestException
     */
    public function createGlobalCustomer($input, $failOnDuplicate = true)
    {
        assertTrue(isset($input[Customer\Entity::CONTACT]));

        return $this->create($input, $this->getSharedAccount(), $failOnDuplicate);
    }

    /**
     * @param Entity          $globalCustomer
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    public function createLocalCustomerFromGlobal(Entity $globalCustomer, Merchant\Entity $merchant)
    {
        if ($globalCustomer->isGlobal() === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_DUPLICATE_NOT_GLOBAL,
                null,
                $globalCustomer->toArray()
            );
        }

        $createInput = [
            Entity::NAME    => $globalCustomer->getName(),
            Entity::EMAIL   => $globalCustomer->getEmail(),
            Entity::CONTACT => $globalCustomer->getContact(),
        ];

        return $this->create($createInput, $merchant, false);
    }

    public function createOrFetchSharedCustomer(Merchant\Entity $merchant)
    {
        $customer = $this->repo->customer->findByContactEmailAndMerchant(
                                                        Entity::SHARED_CUSTOMER_CONTACT,
                                                        Entity::SHARED_CUSTOMER_EMAIL,
                                                        $merchant);

        if ($customer === null)
        {
            $input = [
                Entity::EMAIL   => Entity::SHARED_CUSTOMER_EMAIL,
                Entity::CONTACT => Entity::SHARED_CUSTOMER_CONTACT,
            ];

            $customer = $this->create($input, $merchant, false);
        }

        return $customer;
    }

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param bool            $failOnDuplicate
     *
     * @return Entity
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function create(array $input, Merchant\Entity $merchant, $failOnDuplicate = true)
    {
        $inputTrace = $input;

        unset($inputTrace[Entity::NAME], $inputTrace[Entity::EMAIL], $inputTrace[Entity::CONTACT]);

        $this->trace->info(TraceCode::CUSTOMER_CREATE, $inputTrace);

        $customer = (new Customer\Entity)->build($input);

        $customer->merchant()->associate($merchant);

        $existingCustomer = $this->verifyUniqueCustomer($customer, $failOnDuplicate);

        if ($existingCustomer !== null)
        {
            if ($failOnDuplicate === false)
            {
                $existingCustomer->merchant()->associate($merchant);

                return $existingCustomer;
            }
            else
            {
                throw new Exception\LogicException(
                    'Customer already exists.', null, ['customer_id' => $existingCustomer->getId()]);
            }
        }

        $this->repo->transaction(function() use ($customer, $merchant, $input)
        {
            // This needs to happen here because address create associates itself with the customer.
            // Hence, it's required that the customer is saved.
            $this->repo->saveOrFail($customer);

            $this->createCustomerAddressesIfValuesSetInInput($customer, $input);

        });

        return $customer;
    }

    /**
     * Creates customer addresses if address input keys has been sent as part of
     * create customer request.
     *
     * @param Entity $customer
     * @param array  $input
     *
     * @return null
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function createCustomerAddressesIfValuesSetInInput(Entity $customer, array $input)
    {
        $addressCore = new Address\Core;

        $addressKeys = Address\Type::getValidTypes(Address\Type::CUSTOMER);

        foreach ($addressKeys as $addressKey)
        {
            if (empty($input[$addressKey]) === true)
            {
                continue;
            }

            $input[$addressKey][Address\Entity::TYPE] = $addressKey;

            $addressCore->create($customer, Address\Type::CUSTOMER, $input[$addressKey]);
        }
    }

    public function edit($customer, $input)
    {
        $customer->edit($input);

        $this->verifyUniqueCustomer($customer);

        $this->repo->saveOrFail($customer);

        $inputTrace = $input;

        unset($inputTrace[Entity::NAME], $inputTrace[Entity::EMAIL], $inputTrace[Entity::CONTACT]);

        $this->trace->info(TraceCode::CUSTOMER_EDIT, $inputTrace);

        return $customer;
    }

    public function sendOtp($input, $merchant)
    {
        Locale::setLocale($input, $merchant->getId());

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $data = (new Customer\Raven)->sendOtp($input, $merchant);

        return $data;
    }

    protected function is1ccDemoFlow(array $input, Merchant\Entity $merchant)
    {
        //https://app.asana.com/0/1201308796210994/1201471085908835/f

        if ($merchant === null or $merchant->isFeatureEnabled(FeatureConstants::ONE_CLICK_CHECKOUT) === false)
        {
            return false;
        }

        $otp = $input['otp'];
        $contact = $input['contact'];
        return $merchant->getId() === '10000000000000' and $contact === AccountConstants::DEMO_1CC_CONTACT and $otp === AccountConstants::DEMO_1CC_OTP;
    }

    public function verifyOtp($input, $merchant)
    {
        Locale::setLocale($input, $merchant->getId());

        Customer\Validator::validateGlobalCustomerCreateInput($input);

        // Parse contact
        $input = Customer\Validator::validateAndParseContactInInput($input);

        // 1cc demo OTP hardcode
        if ($this->is1ccDemoFlow($input, $merchant) === false)
        {
            // Verify the otp with raven service
            $this->verifyRavenOtp($input, $merchant);
        }

        if ((empty($input['method']) === false) and
            ($input['method'] === Payment\Method::PAYLATER))
        {
            $token = (new Payment\Service)->generateAndSaveOneTimeTokenWithContact($input);

            return [
                'success'   => 1,
                'ott'       => $token,
            ];
        }
        if ((empty($input['method']) === false) and
            ($input['method'] === Payment\Method::CARDLESS_EMI))
        {
            return $this->fetchCardlessEmiPlansForCustomer($input, $merchant);
        }

        // Get global customer from db or create one.
        $customer = $this->getOrCreateGlobalCustomer($input);

        // Create app token for customer
        $appToken = $this->createCustomerAppToken($customer, $input, $merchant);

        // Fetch existing tokens for global customer
        $tokens = (new Customer\Token\Core)->fetchTokensByCustomerForCheckout($customer, $merchant);

        // Put app token details in session so that we may not
        // need to verify the customer in future.
        $this->putAppTokenInSession($appToken);

        // Create response
        $response = ['success' => 1];

        if ($appToken->merchant->getId() !== $this->getSharedAccount()->getId())
        {
            $response['device_token'] = $appToken->getDeviceToken();
        }

        if ($tokens->isNotEmpty() === true)
        {
            //
            // Currently, we do not expose netbanking recurring tokens to the
            // customer. We don't have a way to handle first recurring
            // with an existing recurring token.
            //

            // TODO: Uncomment this when we use charge_at_will for global flow
            // $tokens = (new Token\Core)->removeEmandateRecurringTokens($tokens);

            $tokens = (new Token\Core)->removeDisabledNetworkTokens($tokens, $merchant->methods->getCardNetworks());

            $tokens = (new Token\Core)->removeCardTokensWithoutName($tokens);

            $tokens = (new Token\Core)->addConsentFieldInTokens($tokens, $merchant);

            $response['tokens'] = $tokens->toArrayPublic();
        }

        if ($this->isCookieDisabledOnBrowser() === true)
        {
            $response['session_id'] = $this->getTemporarySessionToken();
        }

        $response['addresses'] = (new Customer\Core)->fetchRzpAddressesFor1CC($customer);

        return $response;
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function verifyOtp1cc($input, $merchant): array
    {
        $response = $this->verifyOtp($input, $merchant);

        if (empty($response) === false && $response['success'] === 1){

            $customer = $this->getOrCreateGlobalCustomer($input);

            // record address consented details
            if ((empty($input['address_consent']) === false) and
                (empty($input['address_consent']['device_id']) === false))
            {
                $addressConsentInput = [
                    'device_id'   => $input['address_consent']['device_id'],
                ];

                (new Address\Core)->recordAddressConsent1cc($addressConsentInput, $customer);

            }
            if(empty($response['addresses']) === false){
                $rzpAddresses = $response['addresses'];
            }else {
                $rzpAddresses = (new Customer\Core)->fetchRzpAddressesFor1CC($customer);
            }
            $addressConsentView = (new Customer\Core)->fetchAddressConsentViewsFor1CC($customer);
            $thirdPartyAddresses = (new Customer\Core)->fetchThirdPartyAddressesFor1cc($customer);
            $addresses = array_merge($rzpAddresses, $thirdPartyAddresses);

            $response['addresses'] = $addresses;
            $response['1cc_consent_banner_views'] = $addressConsentView;
        }
        return $response;
    }

    public function fetchRzpAddressesFor1CC($customer)
    {
        $addresses = $this->repo->address->fetchRzpAddressesFor1cc($customer);
        return $addresses->sortByDesc(Entity::UPDATED_AT, 1)->values()->all();
    }

    public function fetchThirdPartyAddressesFor1cc($customer): array
    {
        $addressConsentValue = (new Address\Core)->fetchAddressConsent1cc($customer);
        if ($addressConsentValue !== 0){
            $addresses = $this->repo->address->fetchThirdPartyAddressesFor1cc($customer);
            return $addresses->sortByDesc(Entity::UPDATED_AT, 1)->values()->all();
        }
        return [];
    }

    public function fetchAddressConsentViewsFor1CC($customer)
    {
        $remainingViews = 0;
        $addressConsentValue = (new Address\Core)->fetchAddressConsent1cc($customer);
        if ($addressConsentValue !== 0) {
            return $remainingViews;
        }
        $thirdPartyAddressCount = $this->repo->address->fetchThirdPartyAddressCountFor1cc($customer);
        if ($thirdPartyAddressCount === 0) {
            return $remainingViews;
        }
        $addressConsentAudits = (new Address\Core)->fetchAddressConsent1ccAudits($customer->getContact());
        return max(0, 2-$addressConsentAudits);
    }

    /**
     * @throws Exception\BadRequestException
     */
    public function recordAddressConsent1cc($input)
    {
        if(Session()->has($this->mode . '_app_token') === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $appToken = Session()->get($this->mode . '_app_token');

        list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp(
            ['app_token' => $appToken],
            $this->merchant,
            true);

        (new Address\Core)->recordAddressConsent1cc($input, $customer);

        $addresses = $this->fetchThirdPartyAddressesFor1cc($customer);

        return [
            'addresses' => $addresses,
        ];
    }

    /**
     * Used for the Open Wallet demo app - for customer authentication and creation
     * via OTP
     *
     * App sends a request to `/otp/create` to generate an OTP.
     * The OTP and customer contact are sent to `/otp/verify/app` which
     * fetches or creates, and returns a local customer
     *
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function verifyOtpApp(array $input, Merchant\Entity $merchant)
    {
        Customer\Validator::validateWalletAppCustomerCreateInput($input);

        $input = Customer\Validator::validateAndParseContactInInput($input);

        (new Customer\Validator)->validateIndianContact($input[Entity::CONTACT]);

        // Verify the otp with raven service
        $this->verifyRavenOtp($input, $merchant);

        unset($input['otp']);

        $customer = $this->repo->customer->findByContactAndMerchant($input[Entity::CONTACT], $this->merchant);

        if ($customer === null)
        {
            $customer = $this->createLocalCustomer($input, $this->merchant);
        }

        return $customer;
    }

    /**
     * Used for 1 click checkout save address
     * Creates/fetches a global customer and saves address by associating the global customer to that address
     *
     *
     * @param $input
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\InvalidArgumentException
     */
    public function createGlobalAddress($input)
    {
        if(Session()->has($this->mode . '_app_token') === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $appToken = Session()->get($this->mode . '_app_token');

        Customer\Validator::validateCreateGlobalAddress($input);

        $input = Customer\Validator::validateAndParseContactInInput($input);

        list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp(
            ['app_token' => $appToken],
            $this->merchant,
            true);

        // 1cc Demo: Reject address saving for +911234567890
        if ($customer->getContact() === AccountConstants::DEMO_1CC_CONTACT)
        {
            return [];
        }

        $addressEntity = new Address\Core();

        $address = [];

        if ( isset($input[Entity::SHIPPING_ADDRESS]) ) {
            $address[Entity::SHIPPING_ADDRESS] = $addressEntity->create($customer, Address\Type::CUSTOMER, $input[Entity::SHIPPING_ADDRESS], true);
        }
        if ( isset($input[Entity::BILLING_ADDRESS]) ) {
            $address[Entity::BILLING_ADDRESS] = $addressEntity->create($customer, Address\Type::CUSTOMER, $input[Entity::BILLING_ADDRESS], true);
        }

        return $address;

    }

    /**
     * @throws Exception\BadRequestValidationFailureException
     * @throws Exception\BadRequestException
     */
    public function editGlobalAddress($input): array
    {
        if(Session()->has($this->mode . '_app_token') === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        $appToken = Session()->get($this->mode . '_app_token');

        Customer\Validator::validateEditGlobalAddress($input);

        list($customer, $appToken) = (new Customer\Core)->getCustomerAndApp(
            ['app_token' => $appToken],
            $this->merchant,
            true);

        // 1cc Demo: Reject address saving for +911234567890
        if ($customer->getContact() === AccountConstants::DEMO_1CC_CONTACT)
        {
            return [];
        }

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $address = [];

        if ( isset($input[Entity::SHIPPING_ADDRESS]) ) {
            $address[Entity::SHIPPING_ADDRESS] = $this->editAddress($input[Entity::SHIPPING_ADDRESS], $customer);
        }

        if ( isset($input[Entity::BILLING_ADDRESS]) ) {
            $address[Entity::BILLING_ADDRESS] = $this->editAddress($input[Entity::BILLING_ADDRESS], $customer);
        }

        return $address;

    }

    /**
     * Bulk API to add addresses for a customer.
     * TODO: To be merged with createGlobalAddress function.
     * @throws Exception\BadRequestException
     */
    public function createGlobalAddresses($input): array
    {
        if(isset($input['addresses']) === false)
        {
            throw new Exception\BadRequestException();
        }

        $input = Customer\Validator::validateAndParseContactInInput($input);

        $customer = $this->getOrCreateGlobalCustomer($input);

        $addresses = $input['addresses'];
        $invalidAddresses = [];

        foreach ($addresses as $addressInput)
        {
            $addressCore = new Address\Core();
            try
            {
                $addressCore->create($customer, Constants\Entity::ONE_CLICK_CHECKOUT, $addressInput);
            }
            catch (\Throwable $ex)
            {
                array_push($invalidAddresses, $addressInput);
            }
        }

        return $invalidAddresses;
    }

    protected function createCustomerAppToken($customer, $input, $merchant)
    {
        // Currently all app_tokens will be generated for common rzp merchant
        $appMerchant = $customer->merchant;

        if (Base\Utility::isUpdatedAndroidSdk($input))
        {
            $appMerchant = $merchant;
        }

        $custAppInput = [];

        if (isset($input[AppToken\Entity::DEVICE_TOKEN]))
        {
            $custAppInput[AppToken\Entity::DEVICE_TOKEN] = $input[AppToken\Entity::DEVICE_TOKEN];
        }

        $app = (new AppToken\Core)->create($custAppInput, $customer, $appMerchant);

        return $app;
    }

    protected function verifyRavenOtp($input, $merchant)
    {
        try
        {
            $input['merchant_id'] = $merchant->getId();

            (new Customer\Raven)->verifyOtp($input, $merchant);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INCORRECT_OTP);
        }
    }

    /**
     * Gets global customer from db or create one.
     *
     * @param $input
     *
     * @return Entity $contact
     */
    protected function getOrCreateGlobalCustomer($input)
    {
        $contact = $input[Customer\Entity::CONTACT];

        $email = $input[Customer\Entity::EMAIL] ?? null;

        $customer = $this->repo->customer->findByContactAndMerchant(
            $contact,
            $this->getSharedAccount());

        // Create global customer if it does not exist.
        if ($customer === null)
        {
            $custCreateInput = [
                Customer\Entity::CONTACT => $contact,
                Customer\Entity::EMAIL => $email
            ];

            $customer = $this->createGlobalCustomer($custCreateInput);
        }

        return $customer;
    }

    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param bool            $followGlobal
     *
     * We follow global customer flow only for subscriptions currently.
     * We need to do this because of the following case:
     * - A merchant creates a subscription with a global flow.
     * - RZP creates a global customer (since global fow), and ALSO
     *   creates a local customer so that the customer object can be
     *   exposed to the merchant.
     * - Since the local customer object is exposed to the merchant,
     *   he can now use this local customer for something else, like
     *   SmartCollect.
     * - Now, while fetching the customer for SmartCollect flow, we
     *   actually check if the customer has a global customer associated
     *   with it. If it does, we fetch the global customer. This is wrong
     *   because when the merchant used this customer object for SmartCollect,
     *   he meant it to be used as local customer only.
     * - For the above reason, we are adding a hack that only if it's
     *   subscription flow, we check if the customer has an associated
     *   global customer and only then fetch the global customer.
     *   Otherwise, we use the passed local customer only.
     *
     * @return array
     */
    public function getCustomerAndApp(array $input, Merchant\Entity $merchant, bool $followGlobal = false)
    {
        $customerId = null;
        $customer = null;
        $appToken = null;

        //
        // If customer_id is present, it means it's a local customer.
        //
        // If app_token is present, it would always be a global customer.
        //
        // If both are present, we always give preference to the local customer.
        //

        if (empty($input[Payment\Entity::CUSTOMER_ID]) === false)
        {
            $customerId = $input[Payment\Entity::CUSTOMER_ID];

            Customer\Entity::verifyIdAndStripSign($customerId);
        }
        else if (empty($input[Payment\Entity::APP_TOKEN]) === false)
        {
            $appTokenId = $input[Payment\Entity::APP_TOKEN];

            $appToken = (new Customer\AppToken\Core)->getAppByAppTokenId($appTokenId, $merchant);

            if ($appToken !== null)
            {
                $customerId = $appToken->getCustomerId();

                $merchant = $this->repo->merchant->getSharedAccount();
            }
        }

        if ($customerId !== null)
        {
            $customer = $this->repo->customer->findByIdAndMerchant($customerId, $merchant);

            if (($customer->hasGlobalCustomer() === true) and
                ($followGlobal === true))
            {
                list($customer, $appToken) = $this->getCustomerAndAppForGlobal($customer, $merchant, $input);
            }
        }

        $this->trace->info(
            TraceCode::PAYMENT_GET_CUSTOMER,
            [
                'customer_id' => $customerId,
                'app_token'   => $appToken,
            ]);

        return [$customer, $appToken];
    }

    protected function getCustomerAndAppForGlobal(Customer\Entity $customer, Merchant\Entity $merchant, array $input)
    {
        //
        // Even in case of global customer flow, we
        // would be passing local customer only to
        // this function. But, we need to finally return
        // back the global customer for further processing.
        //
        // For global flow, we would need to get both app_token and
        // global_customer which is associated with the local_customer.
        //

        $customer = $customer->globalCustomer;

        $ba = $this->app['basicauth'];

        //
        // In case of internal auth/ crons,
        // there will not be any app_token.
        // Also, in case of subscriptions, we have a charge route (in test mode)
        // (which is generally used by our crons) and also
        // manual invoice charge route (for subscriptions)
        // which is hit from the dashboard. We do not expect to
        // have app_token here just like how we don't expect in
        // privilege (cron) auth.
        //
        if ($ba->isProxyOrPrivilegeAuth() === true)
        {
            return [$customer, null];
        }

        if (empty($input[Payment\Entity::APP_TOKEN]) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APP_TOKEN_ABSENT,
                null,
                [
                    'customer_id' => $customer->getId()
                ]);
        }

        $appToken = (new AppToken\Core)->getAppByAppTokenId(
            $input[Payment\Entity::APP_TOKEN], $merchant);

        if ($appToken === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_APP_TOKEN_NOT_GLOBAL,
                null,
                [
                    'customer_id' => $customer->getId(),
                    'app_token_merchant_id' => $appToken->getMerchantId()
                ]);
        }

        $appTokenCustomerId = $appToken->getCustomerId();
        $customerId = $customer->getId();

        if ($appTokenCustomerId !== $customerId)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_GLOBAL_CUSTOMER_MISMATCH,
                null,
                [
                    'app_token_customer_id' => $appTokenCustomerId,
                    'expected_customer_id' => $customerId,
                ]);
        }

        return [$customer, $appToken];
    }

    public function putAppTokenInSession($appToken)
    {
        // setup session params
        // as device token is public, only app_token is sufficient
        $key = $this->mode . '_app_token';

        $this->trace->info(
            TraceCode::CUSTOMER_CREATE_APP_TOKEN,
            [
                'app_token' => $appToken->getPublicId()
            ]);

        $session = $this->app['request']->getSession();

        if ($session !== null)
        {
            $session->put($key, $appToken->getPublicId());

            $this->trace->info(
                TraceCode::CUSTOMER_SESSION,
                [
                    'session' => $session->all()
                ]);
        }
    }

    protected function isCookieDisabledOnBrowser()
    {
        $key = $this->mode . '_checkcookie';

        $session = $this->app['request']->getSession();

        if ($session !== null)
        {
            return $session->get($key, '0') !== '1';
        }

        return false;
    }

    protected function getTemporarySessionToken()
    {
        $temporaryId = Base\UniqueIdEntity::generateUniqueId();

        $key = 'temp_session:' . $temporaryId;

        $sessionData = [
            'session_id' => $this->app['request']->session()->getId(),
            'user_agent' => $this->app['request']->userAgent(),
            'ip'         => $this->app['request']->ip(),
        ];

        $this->app['cache']->put($key, $sessionData, self::TEMPORARY_SESSION_TIME);

        return $temporaryId;
    }

    protected function verifyUniqueCustomer(Customer\Entity $customer, $failOnDuplicate = true)
    {
        $existingCustomer = null;
        if ($customer->merchant->isShared() === true)
        {
            $existingCustomer = $this->repo->customer->findByContactAndMerchant(
                $customer->getContact(),
                $customer->merchant);
        }
        else if(($customer->getEmail() !== null) or
            ($customer->getContact() !== null) )
        {
            $existingCustomer = $this->repo->customer->findByContactEmailAndMerchant(
                $customer->getContact(),
                $customer->getEmail(),
                $customer->merchant);
        }

        if (($existingCustomer !== null) and
            ($customer->getId() !== $existingCustomer->getId()) and
            ($failOnDuplicate === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }

        return $existingCustomer;
    }

    protected function getSharedAccount()
    {
        return $this->repo->merchant->getSharedAccount();
    }

    public function sendBalanceEnqRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = [
            'device'       => $device->toArray(),
            'customer'     => $customer->toArrayPublic(),
            'bank_account' => $bankAccount->toArray(),
            'gateway'        => $input
        ];

        $params = [
            'method' => 'ReqBalEnq',
            'params' => $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendOtpRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    => 'ReqOtp',
            'params'    => $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendSetMpinRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    => 'ReqRegMob',
            'params'    => $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendResetMpinRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    => 'ReqSetCre',
            'params'    => $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    protected function getGatewayInputParams(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput['device'] = $device->toArray();
        $gatewayInput['customer'] = $customer->toArrayPublic();
        $gatewayInput['bank_account'] = $bankAccount->toArray();
        $gatewayInput['input'] = $input;

        return $gatewayInput;
    }

    protected function fetchCardlessEmiPlansForCustomer($input, $merchant)
    {
        // we make use of cache to fetch the plans. The terminal passed here is used only to set the gateway
        // We are not fetching terminal here as this would require routing logic and is not necessary here
        $terminal = [];

        if (Payment\Processor\PayLater::exists($input['provider']) === true)
        {
            $terminal[Terminal\Entity::GATEWAY] = Payment\Gateway::PAYLATER;
        }
        else
        {
            $terminal[Terminal\Entity::GATEWAY] = Payment\Gateway::CARDLESS_EMI;
        }

        // merchant id is required to fetch details from cache
        $input['merchant_id'] = $merchant['id'];

        list($emiPlans, $loanUrl) =
            $this->app['gateway']->call(Payment\Gateway::CARDLESS_EMI, 'get_emi_plans', $input, $this->mode, $terminal);

        unset($input['merchant_id']);

        $token = (new Payment\Service)->generateAndSaveOneTimeTokenWithContact($input);

        $data = [
            'success'   => 1,
            'emi_plans' => $emiPlans,
            'ott'       => $token,
            'loan_url'  => $loanUrl
        ];

        return $data;
    }

    /**
     * @param $input
     * @param Base\PublicEntity $customer
     * @return array
     * @throws Exception\BadRequestException
     * @throws Exception\BadRequestValidationFailureException
     */
    public function editAddress($input, Base\PublicEntity $customer)
    {
        $addressCore = new Address\Core();

        try
        {
            $address = $this->repo->address->findByEntityAndId($input[Base\UniqueIdEntity::ID], $customer);
        }
        catch (\Throwable $ex)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR,null,null,PublicErrorDescription::BAD_REQUEST_CUSTOMER_ADDRESS_NOT_FOUND);
        }

        return $addressCore->edit($address, $input);
    }
}
