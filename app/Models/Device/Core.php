<?php

namespace RZP\Models\Device;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Upi;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @var Upi\Core
     */
    protected $upiCore;

    public function __construct()
    {
        parent::__construct();

        $this->upiCore = new Upi\Core;
    }

    public function create(array $input, Merchant\Entity $merchant)
    {
        $device = new Entity();

        $device->build($input);

        $device->merchant()->associate($merchant);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function verify(Entity $device, Customer\Entity $customer)
    {
        if ($device->hasBeenVerified() === true)
        {
            return $device;
        }

        $device->setStatus(Status::VERIFIED);

        $device->customer()->associate($customer);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function updateChallenge(Entity $device, string $challenge)
    {
        $device->setChallenge($challenge);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function sendGetTokenRequestToGateway(Entity $device, Customer\Entity $customer, $challengeType = 'initial')
    {
        $gatewayInput['device'] = $device->toArray();
        $gatewayInput['customer'] = $customer->toArrayPublic();
        $gatewayInput['challengeType'] = $challengeType;

        $response = $this->upiCore->callUpiGateway('GetToken', $gatewayInput);

        return $response;
    }

    /**
     * Note: This gets called for all token updates
     * including initial/rotate/reset
     * @param  Entity $device   [description]
     * @param  string $upiToken [description]
     * @return [type]           [description]
     */
    public function updateUpiToken(Entity $device, string $upiToken)
    {
        $device->setUpiToken($upiToken);

        if ($device->hasBeenRegistered() === false)
        {
            $device->setStatus(Status::REGISTERED);

            $this->createBankAccountForCustomer($device);
        }

        $this->repo->saveOrFail($device);

        return $device;
    }

    protected function createBankAccountForCustomer(Entity $device)
    {
        $customer = $device->customer;

        $currentAccounts = $this->repo->bank_account->getBankAccountsForCustomer($customer);

        if ($currentAccounts->isEmpty() === false)
        {
            return;
        }

        $inputs = $this->getTestBankAccountDetails($customer);

        (new BankAccount\Core)->addOrUpdateBankAccountForCustomer($inputs[0], $customer);
        (new BankAccount\Core)->addOrUpdateBankAccountForCustomer($inputs[1], $customer);
    }

    protected function getTestBankAccountDetails(Customer\Entity $customer)
    {
        $input1 = [
            'ifsc_code'             => 'RAZR0000001',
            'account_number'        => $customer->getContact() . '1',
            'beneficiary_name'      => 'Test R4zorpay:',
            'beneficiary_address1'  => 'address 1',
            'beneficiary_address2'  => 'address 2',
            'beneficiary_address3'  => 'address 3',
            'beneficiary_address4'  => 'address 4',
            'beneficiary_email'     => 'test@razorpay.com',
            'beneficiary_mobile'    => $customer->getContact(),
            'beneficiary_city'      => 'Bangalore',
            'beneficiary_state'     => 'KA',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '123456',
        ];

        $input2 = [
            'ifsc_code'             => 'RAZR0000001',
            'account_number'        => $customer->getContact(),
            'beneficiary_name'      => 'Test R4zorpay:',
            'beneficiary_address1'  => 'address 1',
            'beneficiary_address2'  => 'address 2',
            'beneficiary_address3'  => 'address 3',
            'beneficiary_address4'  => 'address 4',
            'beneficiary_email'     => 'test2@razorpay.com',
            'beneficiary_mobile'    => $customer->getContact(),
            'beneficiary_city'      => 'Kolkata',
            'beneficiary_state'     => 'WB',
            'beneficiary_country'   => 'IN',
            'beneficiary_pin'       => '123456',
        ];

        return [$input1, $input2];
    }
}
