<?php

namespace Models\Customer;

use RZP\Error\ErrorCode;
use RZP\Exception;
use Models\Base;
use Models\Customer;
use Models\Merchant;
use Models\Merchant\Account;
use Models\Payment;
use Trace\TraceCode;

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

    public function create($input, $merchant)
    {
        $customer = (new Customer\Entity)->build($input);

        $customer->merchant()->associate($merchant);

        $this->verifyUniqueCustomer($customer);

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

    public function sendOtp($input)
    {
        $input[Entity::CONTACT] = Customer\Validator::validateAndParseContact(
            $input[Entity::CONTACT]);

        $data = (new Customer\Raven)->sendOtp($input);

        return $data;
    }

    public function verifyOtp($input)
    {
        //validate and parse contact
        $input[Entity::CONTACT] = Customer\Validator::validateAndParseContact(
            $input[Entity::CONTACT]);

        // Verify the otp with raven service
        $this->verifyRavenOtp($input);

        // Get global customer from db or create one.
        $customer = $this->getOrCreateGlobalCustomer($input);

        // Create app token for customer
        $appToken = $this->createCustomerAppToken($customer, $input);

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

    protected function createCustomerAppToken($customer, $input)
    {
        // Currently all app_tokens will be generated for common rzp merchant
        $appMerchant = $customer->merchant->getId();

        // @todo: switch to merchant for newer sdk based on query params

        $custAppInput = array(
            App\Entity::CUSTOMER_ID => $customer->getId(),
            App\Entity::MERCHANT_ID => $appMerchant);

        if (isset($input[App\Entity::DEVICE_TOKEN]))
        {
            $custAppInput[App\Entity::DEVICE_TOKEN] = $input[App\Entity::DEVICE_TOKEN];
        }

        $app = (new App\Core)->create($custAppInput);

        return $app;
    }

    protected function verifyRavenOtp($input)
    {
        try
        {
            (new Customer\Raven)->verifyOtp($input);
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
        $customerApp = null;

        if (empty($input[Payment\Entity::APP_TOKEN]) === false)
        {
            $appToken = $input[Payment\Entity::APP_TOKEN];

            Customer\App\Entity::verifyIdAndStripSign($appToken);

            $customerApp = (new Customer\App\Core)->getAppByAppToken(
                $appToken,
                $merchant);

            $customerId = $customerApp->getCustomerId();

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

        return array($customer, $customerApp);
    }

    protected function putAppTokenInSession($appToken)
    {
        // setup session params
        // as device token is public, only app_token is sufficient
        $this->app['session']->put('app_token', $appToken->getPublicId());
    }

    protected function verifyUniqueCustomer($customer)
    {
        $customers = null;

        if ($customer->merchant->isShared() === true)
        {
            $customers = $this->repo->customer->findByContactAndMerchant(
                $customer->getContact(),
                $customer->merchant);
        }
        else
        {
            $customers = $this->repo->customer->findByContactEmailAndMerchant(
                $customer->getContact(),
                $customer->getEmail(),
                $customer->merchant);
        }

        if ($customers !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }
    }

    protected function getSharedAccount()
    {
        return $this->repo->merchant->getSharedAccount();
    }
}