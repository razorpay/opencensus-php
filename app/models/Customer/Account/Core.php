<?php

namespace Models\Customer;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Customer;
use Models\Merchant\Account;
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

        if (isset($data['result']) and $data['result'] === 1)
        {
            $customer = $this->repo->findByContactForMerchant(
                $input[Customer\Entity::CONTACT],
                Account::SHARED_ACCOUNT
            );

            if ($customer === null)
            {
                $custCreateInput = array(
                    Customer\Entity::CONTACT        =>   $input[Customer\Entity::CONTACT],
                    Customer\Entity::MERCHANT_ID    =>   Account::SHARED_ACCOUNT,
                );

                $customer = $this->create($custCreateInput);
            }

            $custAppInput = array(
                App\Entity::CUSTOMER_ID => $customer->getId(),
                App\Entity::MERCHANT_ID => $input['context'],
                App\Entity::DEVICE_ID   => $input[App\Entity::DEVICE_ID],
                App\Entity::APP_ID      => Base\UniqueIdEntity::generateUniqueId(),
            );

            $app = (new App\Core)->create($custAppInput);

            $response[App\Entity::APP_ID] = $app->getAppId();
            $response['result'] = 1;
        }
        else
        {
            $response['result'] = 0;
            $response['error'] = $data['error'];
        }

        return $response;
    }

    protected function verifyUniqueCustomer($customer)
    {
        $customer = $this->repo->findByContactForMerchant($customer->getContact(), $customer->merchant->getId());

        if ($customer !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS);
        }
    }
}