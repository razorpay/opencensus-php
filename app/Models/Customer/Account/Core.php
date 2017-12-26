<?php

namespace RZP\Models\Customer;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Address;
use RZP\Models\Device;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment;
use RZP\Models\BankAccount;
use RZP\Models\Upi;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     * @param bool            $failOnDuplicate
     *
     * @return Entity
     * @throws Exception\LogicException
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
        $this->trace->info(TraceCode::CUSTOMER_CREATE, $input);

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

        $this->trace->info(TraceCode::CUSTOMER_EDIT, $input);

        return $customer;
    }

    public function sendOtp($input, $merchant)
    {
        $input = Customer\Validator::validateAndParseContactInInput($input);

        $data = (new Customer\Raven)->sendOtp($input, $merchant);

        return $data;
    }

    public function verifyOtp($input, $merchant)
    {
        // Currently, the validator does not have any mandatory field.
        Customer\Validator::validateGlobalCustomerCreateInput($input);

        // Parse contact
        $input = Customer\Validator::validateAndParseContactInInput($input);

        // Verify the otp with raven service
        $this->verifyRavenOtp($input, $merchant);

        // Get global customer from db or create one.
        $customer = $this->getOrCreateGlobalCustomer($input);

        // Create app token for customer
        $appToken = $this->createCustomerAppToken($customer, $input, $merchant);

        // Fetch existing tokens for global customer
        $tokens = (new Customer\Token\Core)->fetchTokensByCustomer($customer);

        // Put app token details in session so that we may not
        // need to verify the customer in future.
        $this->putAppTokenInSession($appToken);

        // Create response
        $response = array('success' => 1);

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

            $response['tokens'] = $tokens->toArrayPublic();
        }

        return $response;
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

    protected function createCustomerAppToken($customer, $input, $merchant)
    {
        // Currently all app_tokens will be generated for common rzp merchant
        $appMerchant = $customer->merchant->getId();

        if (Base\Utility::isUpdatedAndroidSdk($input))
        {
            $appMerchant = $merchant->getId();
        }

        $custAppInput = array(
            AppToken\Entity::CUSTOMER_ID => $customer->getId(),
            AppToken\Entity::MERCHANT_ID => $appMerchant);

        if (isset($input[AppToken\Entity::DEVICE_TOKEN]))
        {
            $custAppInput[AppToken\Entity::DEVICE_TOKEN] = $input[AppToken\Entity::DEVICE_TOKEN];
        }

        $app = (new AppToken\Core)->create($custAppInput);

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
        $email = $input[Customer\Entity::EMAIL];

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

    public function getCustomerAndApp(array $input, Merchant\Entity $merchant)
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

            if ($customer->hasGlobalCustomer() === true)
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

        return array($customer, $appToken);
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
        // (which is generally used by our crons)
        // which is hit from the dashboard. We do not expect to
        // have app_token here just like how we don't expect in
        // privilege (cron) auth.
        //
        if ((($ba->isProxyAuth() === true) and
             ($this->mode === Mode::TEST)) or
            ($ba->isPrivilegeAuth() === true))
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

        $this->app['request']->session()->put($key, $appToken->getPublicId());

        $this->trace->info(
            TraceCode::CUSTOMER_SESSION,
            [
                'session' => $this->app['request']->session()->all()
            ]);
    }

    protected function verifyUniqueCustomer(Customer\Entity $customer, $failOnDuplicate = true)
    {
        if ($customer->merchant->isShared() === true)
        {
            $existingCustomer = $this->repo->customer->findByContactAndMerchant(
                $customer->getContact(),
                $customer->merchant);
        }
        else
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
            'method' =>  'ReqBalEnq',
            'params' =>  $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendOtpRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    =>  'ReqOtp',
            'params'    =>  $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendSetMpinRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    =>  'ReqRegMob',
            'params'    =>  $gatewayInput
        ];

        $response = (new Upi\Core)->callUpiGateway('makeRequest', $params);

        return $response;
    }

    public function sendResetMpinRequestToGateway(
        Device\Entity $device, Entity $customer, BankAccount\Entity $bankAccount, array $input)
    {
        $gatewayInput = $this->getGatewayInputParams($device, $customer, $bankAccount, $input);

        $params = [
            'method'    =>  'ReqSetCre',
            'params'    =>  $gatewayInput
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
}
