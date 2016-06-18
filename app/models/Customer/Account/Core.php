<?php

namespace Models\Customer;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Customer;
use Models\Merchant;
use Models\Merchant\Account;
use Models\Payment;
use Trace\TraceCode;
use Session;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function create($input, $merchant)
    {
        $customer = (new Customer\Entity)->build($input);

        $customer->merchant()->associate($merchant);

        $this->verifyUniqueCustomer($customer);

        $this->repo->saveOrFail($customer);

        return $customer;
    }

    public function createGlobalCustomer($input)
    {
        assert(isset($input[Customer\Entity::CONTACT]));

        $merchant = $this->repo->merchant->findOrFail(Account::SHARED_ACCOUNT);

        return $this->create($input, $merchant);
    }

    public function edit($customer, $input)
    {
        $customer->edit($input);

        $this->verifyUniqueCustomer($customer);

        $this->repo->saveOrFail($customer);

        $this->trace->info(
            TraceCode::CUSTOMER_EDIT,
            [$input]);

        return $customer;
    }

    public function verifyOtp($input)
    {
        $response = array();
        $data = null;

        $this->verifyOtpIsSuccessOrFail($input);

        // Get global customer from db or create one.
        $customer = $this->getOrCreateGlobalCustomer($input[Customer\Entity::CONTACT]);

        $custAppInput = array(
            App\Entity::CUSTOMER_ID => $customer->getId(),
            App\Entity::MERCHANT_ID => $input['context']);

        if (isset($input[App\Entity::DEVICE_TOKEN]))
        {
            $custAppInput[App\Entity::DEVICE_TOKEN] = $input[App\Entity::DEVICE_TOKEN];
        }

        $app = (new App\Core)->create($custAppInput);

        $merchant = $this->repo->merchant->findOrFail(Account::SHARED_ACCOUNT);

        $tokens = (new Customer\Token\Core)->fetchTokensByCustomerAndMerchant(
            $customer, $merchant);

        $response['success'] = 1;
        $response['app_token'] = $app->getPublicId();
        $response['device_token'] = $app->getDeviceToken();


        Session::put('app_token', $app->getPublicId());
        Session::put('device_token', $app->getDeviceToken());

        if (($tokens !== null) and ($tokens->count() > 0))
        {
            $response['tokens'] = $tokens->toArrayPublic();
        }

        return $response;
    }

    protected function verifyOtpIsSuccessOrFail($input)
    {
        try
        {
            $data = (new Customer\Raven)->verifyOtp($input);
        }
        catch (\Exception $e)
        {
            $data['success'] = false;

            $this->trace->traceException($e);
        }

        if ($data['success'] === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_OTP);
        }
    }

    /**
     * Gets global customer from db or create one.
     * @param  string $contact customer's phone number
     * @return Customer\Entity $contact
     */
    protected function getOrCreateGlobalCustomer($contact)
    {
        $customer = $this->repo->customer->findByContactForMerchant(
            $contact,
            Account::SHARED_ACCOUNT);

        // Create global customer if it does not exist.
        if ($customer === null)
        {
            $custCreateInput = [Customer\Entity::CONTACT => $contact];

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

            $customerApp = (new Customer\App\Repository)->findByIdAndMerchantId(
                $appToken,
                $merchant->getId());

            assert($customerApp !== null);

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

    protected function verifyUniqueCustomer($customer)
    {
        $customers = null;

        if ($customer->merchant->isShared() === true)
        {
            $customers = $this->repo->customer->findByContactForMerchant(
                $customer->getContact(),
                $customer->merchant->getId());
        }
        else
        {
            $customers = $this->repo->customer->findByContactEmailForMerchant(
                $customer->getContact(),
                $customer->getEmail(),
                $customer->merchant->getId());
        }

        if ($customers !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }
    }
}