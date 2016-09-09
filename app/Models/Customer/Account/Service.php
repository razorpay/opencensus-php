<?php

namespace RZP\Models\Customer;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Account;
use RZP\Models\BankAccount;
use RZP\Models\Payment;

class Service extends Base\Service
{
    /**
     * Creates Local customer entity for merchant
     * @param  array customer data
     * @return array customer data
     */
    public function createLocalCustomer($input)
    {
        $failOnDuplicate = true;

        if ((isset($input['flag'])) and
            ($input['flag'] === '1'))
        {
            $failOnDuplicate = false;
        }

        unset($input['flag']);

        $customer = (new Customer\Core)->createLocalCustomer($input, $this->merchant, $failOnDuplicate);

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

    public function addBankAccount($id, $input)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $ba = (new BankAccount\Core)->addOrUpdateBankAccountForCustomer($input, $customer);

        return $ba->toArrayPublic();
    }

    public function getBankAccounts($id)
    {
        Customer\Entity::verifyIdAndStripSign($id);

        $customer = $this->repo->customer->findByIdAndMerchantId($id, $this->merchant->getId());

        $accounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        return $accounts->toArrayPublic();
    }

    /**
     * Send Oto to customer
     *
     * @param  details of customer for otp send
     * @return success/failure
     */
    public function sendOtp($input)
    {
        $data = (new Customer\Core)->sendOtp($input, $this->merchant);

        return $data;
    }

    /**
     * @param  otp verification data
     * @return success with tokens or failure
     */
    public function verifyOtp($input)
    {
        $data = (new Customer\Core)->verifyOtp($input, $this->merchant);

        return $data;
    }

    /**
     * @param  check global customer existance and send otp
     * @param  boolean if to send otp or not
     * @return global customer existance, send otp if true
     */
    public function fetchGlobalCustomerStatus($contact, $input, $sendOtp = false)
    {
        $data = ['saved' => false];

        $merchant = $this->repo->merchant->getSharedAccount();

        $contact = Customer\Validator::validateAndParseContact($contact);

        $customer = $this->repo->customer->findByContactAndMerchant($contact, $merchant);

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
            $custAppInput = array(
                AppToken\Entity::CUSTOMER_ID     => $customer->getId(),
                AppToken\Entity::MERCHANT_ID     => $this->merchant->getId(),
                AppToken\Entity::DEVICE_TOKEN    => $deviceToken);

            $app = (new AppToken\Core)->create($custAppInput);

            (new Customer\Core)->putAppTokenInSession($app);

            // Fetch existing tokens if exists
            $tokens = (new Customer\Token\Core)->fetchTokensByCustomer($customer);

            if (($tokens !== null) and ($tokens->count() > 0))
            {
                $result['tokens'] = $tokens->toArrayPublic();
            }
        }

        return $result;
    }

    public function updateSmsStatus($id, $input)
    {
        $data = (new Customer\Raven)->updateSmsStatus($id, $input);

        return $data;
    }

    public function fetchPaymentsForGlobalCustomer($input)
    {
        Customer\Validator::validateFetchCustomerPaymentsInput($input);

        $skip = 0;

        if (empty($input['skip']) === false)
        {
            $skip = $input['skip'];
        }

        $appTokenId = AppToken\SessionHelper::getAppTokenFromSession($this->mode);

        $payments = new Base\PublicCollection;

        if ($appTokenId !== null)
        {
            AppToken\Entity::verifyIdAndStripSign($appTokenId);

            $appToken = (new AppToken\Core)->getAppByAppToken($appTokenId, $this->merchant);

            $payments = $this->repo->payment->fetchPaymentsForCustomerMethod(
                $appToken->customer,
                Payment\Method::CARD,
                $skip);
        }

        $collection = new Base\PublicCollection;

        foreach ($payments as $payment)
        {
            $info = array(
                'merchant'  => $payment->merchant->getBillingLabelElseName(),
                'card'      => $payment->card->getLast4(),
                'amount'    => $payment->getAmount(),
                'time'      => $payment->getCaptureTimestamp(),
                'id'        => $payment->getPublicId());

            $collection->push($info);
        }

        return $collection->toArrayWithItems();
    }

    public function createAddress($customerId, array $input)
    {
        Entity::verifyIdAndStripSign($customerId);

        $customer = $this->repo->customer->findByIdAndMerchant($customerId, $this->merchant);

        $address = (new Address\Core)->create($customer, $input);

        return $address->toArrayPublic();
    }
}

