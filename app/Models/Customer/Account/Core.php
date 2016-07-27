<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createLocalCustomer($input, $merchant)
    {
        return $this->create($input, $merchant);
    }

    public function createGlobalCustomer($input)
    {
        assert(isset($input[Customer\Entity::CONTACT]));

        return $this->create($input, $this->getSharedAccount());
    }

    protected function create($input, $merchant, $failOnDuplicate = true)
    {
        $customer = (new Customer\Entity)->build($input);

        $customer->merchant()->associate($merchant);

        $existingCustomer = $this->verifyUniqueCustomer($customer, $failOnDuplicate);

        if ($existingCustomer !== null)
        {
            if ($failOnDuplicate === false)
            {
                $existingCustomer->merchant->associate($merchant);
                return $existingCustomer;
            }
            else
            {
                throw new Exception\LogicException(
                    'Should not reach here');
            }
        }

        $this->repo->saveOrFail($customer);

        return $customer;
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
        $input[Entity::CONTACT] = Customer\Validator::validateAndParseContact(
            $input[Entity::CONTACT]);

        $data = (new Customer\Raven)->sendOtp($input, $merchant);

        return $data;
    }

    public function verifyOtp($input, $merchant)
    {
        Customer\Validator::validateGlobalCustomerCreateInput($input);

        // Parse contact
        $input[Entity::CONTACT] = Customer\Validator::validateAndParseContact(
            $input[Entity::CONTACT]);

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

        if (($tokens !== null) and ($tokens->count() > 0))
        {
            $response['tokens'] = $tokens->toArrayPublic();
        }

        return $response;
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
     * @param  string $contact customer's phone number
     * @return Customer\Entity $contact
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

    public function getCustomerAndApp($input, $merchant)
    {
        $customerId = null;
        $merchantId = null;
        $customer = null;
        $appToken = null;
        $appToken = null;

        if (empty($input[Payment\Entity::APP_TOKEN]) === false)
        {
            $appToken = $input[Payment\Entity::APP_TOKEN];

            Customer\AppToken\Entity::verifyIdAndStripSign($appToken);

            $appToken = (new Customer\AppToken\Core)->getAppByAppToken(
                $appToken,
                $merchant);

            $customerId = $appToken->getCustomerId();

            $merchantId = Account::SHARED_ACCOUNT;
        }
        else if (empty($input[Payment\Entity::CUSTOMER_ID]) === false)
        {
            $merchantId = $merchant->getId();

            $customerId = $input[Payment\Entity::CUSTOMER_ID];

            Customer\Entity::verifyIdAndStripSign($customerId);
        }

        if ($customerId !== null)
        {
            $customer = $this->repo->customer->findByIdAndMerchantId($customerId, $merchantId);
        }

        $this->trace->info(
            TraceCode::PAYMENT_GET_CUSTOMER,
            [
                'customer_id' => $customerId,
                'app_token'   => $appToken,
            ]);

        return array($customer, $appToken);
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


        // sd($appToken->getPublicId(), $this->app['session']->get('app_token'));
    }

    protected function verifyUniqueCustomer($customer, $failOnDuplicate = true)
    {
        $customers = null;

        if ($customer->merchant->isShared() === true)
        {
            $customer = $this->repo->customer->findByContactAndMerchant(
                $customer->getContact(),
                $customer->merchant);
        }
        else
        {
            $customer = $this->repo->customer->findByContactEmailAndMerchant(
                $customer->getContact(),
                $customer->getEmail(),
                $customer->merchant);
        }

        if (($customer !== null) and
            ($failOnDuplicate === true))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }

        return $customer;
    }

    protected function getSharedAccount()
    {
        return $this->repo->merchant->getSharedAccount();
    }
}
