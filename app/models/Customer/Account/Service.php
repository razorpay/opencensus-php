<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer;
use Models\Merchant;
use Models\Merchant\Account;

class Service extends Base\Service
{
    protected $repo;

    public function __construct()
    {
        parent::__construct();

        $this->repo = new Customer\Repository;
    }

    public function create($input)
    {
        return $this->createCustomer($input, $this->merchant);
    }

    public function createGlobalCustomer($input)
    {
        assert(isset($input[Customer\Entity::CONTACT]));

        $merchant = (new Merchant\Repository)->findOrFail(Account::SHARED_ACCOUNT);

        return $this->createCustomer($input, $merchant);
    }

    public function edit($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = (new Customer\Core)->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    public function fetch($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        return $customer->toArrayPublic();
    }

    public function delete($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = $this->repo->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    public function sendOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $data = (new Customer\Raven)->sendOtp($input);

        return $data;
    }

    public function verifyOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $data = (new Customer\Core)->verifyOtp($input);

        return $data;
    }

    public function fetchCustomerStatus($contact, $sendOtp = false)
    {
        $data = array();

        $merchant = (new Merchant\Repository)->findOrFail(Account::SHARED_ACCOUNT);

        $customer = $this->repo->findByContactForMerchant($contact, Account::SHARED_ACCOUNT);

        if ($customer !== null)
        {
            $data = (new Customer\Token\Core)->fetchCustomerStatus($customer, $merchant);

            if ((isset($data['saved'])) and
                ($data['saved'] === true) and
                ($sendOtp == true))
            {
                $this->sendOtp(array('contact' => $contact));
            }
        }
        else
        {
            $data['saved'] = false;
        }

        return $data;
    }

    public function validateDeviceToken($deviceToken, $input)
    {
        $valid = false;

        $contact = $input['contact'];

        $customer = $this->repo->findByContactForMerchant($contact, Account::SHARED_ACCOUNT);

        if ($customer !== null)
        {
            $valid = (new Customer\App\Core)->validateDeviceToken($deviceToken, $customer, $this->merchant);
        }

        $result = array(
            'valid' => $valid);


        if ($valid === true)
        {
            $custAppInput = array(
                App\Entity::CUSTOMER_ID     => $customer->getId(),
                App\Entity::MERCHANT_ID     => $this->merchant->getId(),
                App\Entity::DEVICE_TOKEN    => $deviceToken);

            $app = (new App\Core)->create($custAppInput);

            $result['app_token'] = $app->getPublicId();
        }

        return $result;
    }

    public function updateSmsStatus($id, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($id, $input);

        return $data;
    }

    protected function createCustomer($input, $merchant)
    {
        $customer = (new Customer\Core)->create($input, $merchant);

        return $customer->toArrayPublic();
    }
}

