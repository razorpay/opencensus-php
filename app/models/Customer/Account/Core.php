<?php

namespace Models\Customer;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Customer;
use Models\Merchant\Account;
use Models\Payment;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;
    }

    public function create($input)
    {
        $customer = (new Customer\Entity)->build($input);

        $this->verifyUniqueCustomer($customer);

        $this->repo->saveOrFail($customer);

        return $customer;
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
        $data = (new Customer\Raven)->verifyOtp($input);

        $response = array();

        if ((isset($data['success'])) and ($data['success'] === true))
        {
            $customer = $this->repo->findByContactForMerchant(
                $input[Customer\Entity::CONTACT],
                Account::SHARED_ACCOUNT);

            if ($customer === null)
            {
                $custCreateInput = array(
                    Customer\Entity::CONTACT        =>   $input[Customer\Entity::CONTACT],
                    Customer\Entity::MERCHANT_ID    =>   Account::SHARED_ACCOUNT);

                $customer = $this->create($custCreateInput);
            }

            $custAppInput = array(
                App\Entity::CUSTOMER_ID => $customer->getId(),
                App\Entity::MERCHANT_ID => $input['context'],
                App\Entity::DEVICE_ID   => $input[App\Entity::DEVICE_ID]);

            $app = (new App\Core)->create($custAppInput);

            $tokens = (new Customer\Token\Core)->fetchTokensByCustomerId(
                Account::SHARED_ACCOUNT, $customer->getId());

            $response['success'] = 1;
            $response['app_id'] = $app->getPublicId();

            if ($tokens !== null)
            {
                $response['tokens'] = $tokens->toArrayPublic();
            }
        }
        else
        {
            $response['success'] = 0;

            $response['error'] = "otp verification failed";
        }

        return $response;
    }

    public function getCustomerAndApp($input, $merchant)
    {
        $customerId = null;
        $merchantId = null;
        $customer = null;
        $customerApp = null;

        if (empty($input[Payment\Entity::APP_ID]) === false)
        {
            $appId = $input[Payment\Entity::APP_ID];

            Customer\App\Entity::verifyIdAndStripSign($appId);

            $customerApp = (new Customer\App\Repository)->findByIdAndMerchantId(
                $appId,
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
            $customer = $this->repo->findByIdAndMerchantId($customerId, $merchantId);
        }

        return array($customer, $customerApp);
    }

    protected function verifyUniqueCustomer($customer)
    {
        $customer = $this->repo->findByContactForMerchant(
                        $customer->getContact(), $customer->merchant->getId());

        if ($customer !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }
    }
}