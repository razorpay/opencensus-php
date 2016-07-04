<?php

namespace Models\Customer;

use Models\Base;
use Models\Customer;
use Models\Merchant;
use Models\Merchant\Account;

class Service extends Base\Service
{
    /**
     * Creates Local customer entity for merchant
     * @param  array customer data
     * @return array customer data
     */
    public function createLocalCustomer($input)
    {
        $customer = (new Customer\Core)->createLocalCustomer($input, $this->merchant);

        return $customer->toArrayPublic();
    }

    /**
     * Creates Global customer entity for shared merchant
     * @param  array customer data
     * @return array customer data
     */
    public function createGlobalCustomer($input)
    {
        $customer = (new Customer\Core)->createGlobalCustomer($input);

        return $customer->toArrayPublic();
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
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = (new Customer\Core)->edit($customer, $input);

        return $customer->toArrayPublic();
    }

    /**
     * Fetch local customer using id
     *
     * @param  string id of the customer
     * @return array customer details
     */
    public function fetch($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        return $customer->toArrayPublic();
    }

    /**
     * Delete a local customer
     *
     * @param  local customer id
     * @return deleted customer
     */
    public function delete($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $customer = $this->repo->customer->deleteOrFail($customer);

        if ($customer === null)
            return [];

        return $customer->toArrayPublic();
    }

    /**
     * Send Oto to customer
     *
     * @param  details of customer for otp send
     * @return success/failure
     */
    public function sendOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $input['params']['merchant_name'] = $this->merchant->getBillingLabelElseName();

        $data = (new Customer\Core)->sendOtp($input);

        return $data;
    }

    /**
     * @param  otp verification data
     * @return success with tokens or failure
     */
    public function verifyOtp($input)
    {
        $input['context'] = $this->merchant->getId();

        $input['source'] = 'api';

        $data = (new Customer\Core)->verifyOtp($input);

        return $data;
    }

    /**
     * @param  check global customer existance and send otp
     * @param  boolean if to send otp or not
     * @return global customer existance, send otp if true
     */
    public function fetchGlobalCustomerStatus($contact, $sendOtp = false)
    {
        $data = ['saved' => false];

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($contact);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        if ($customer !== null)
        {
            $data['saved'] = true;

            if ($sendOtp === true)
            {
                $this->sendOtp(['contact' => $contact]);
            }
        }

        return $data;
    }

    /**
     * Validates if device token is valid device token for a contact
     * @param  deviceToken to be validated
     * @param  input params
     * @return issues a new app_token if device_token is valid
     */
    public function validateDeviceToken($deviceToken, $input)
    {
        $result = ['valid' => false];

        $contact = Customer\Validator::validateAndParseContact($input[Entity::CONTACT]);

        $merchant = $this->repo->merchant->getSharedAccount();

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

        if ($customer !== null)
        {
            $apps = $this->repo->app_token->fetchAppsByDeviceToken(
                $customer,
                $deviceToken);

            if (($apps !== null) and ($apps->count() > 0))
            {
                $result['valid'] = true;
            }
        }

        // If result is valid, then create a new app token.
        if ($result['valid'] === true)
        {
            $custAppInput = array(
                App\Entity::CUSTOMER_ID     => $customer->getId(),
                App\Entity::MERCHANT_ID     => $this->merchant->getId(),
                App\Entity::DEVICE_TOKEN    => $deviceToken);

            $app = (new App\Core)->create($custAppInput);

            $result['app_token'] = $app->getPublicId();
            $result['email'] = $customer->getEmail();
        }

        return $result;
    }

    public function updateSmsStatus($id, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($id, $input);

        return $data;
    }
}

