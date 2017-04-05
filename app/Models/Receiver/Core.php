<?php

namespace RZP\Models\Receiver;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\BankAccount;

class Core extends Base\Core
{
    const ACCOUNT_NUMBER_LENGTH = 20;

    public function addVirtualBankAccountForCustomer($customer)
    {
        $ba = $this->buildBankAccount($this->merchant);

        $ba->associateCustomer($customer);

        $ba->setVirtual(true);

        $this->createReceiverFromBankAccount($ba);

        $this->repo->saveOrFail($ba);

        return $ba;
    }

    public function addStandingBankAccount()
    {
        $ba = $this->buildBankAccount($this->merchant);

        $ba->associateMerchant($this->merchant);

        $ba->setVirtual(true);

        $this->createReceiverFromBankAccount($ba, false);

        $this->repo->saveOrFail($ba);

        return $ba;
    }

    protected function createReceiverFromBankAccount($bankAccount, $oneTimeUse = true)
    {
        $receiver = new Entity;

        $receiver->entityAssociate($bankAccount);

        $receiver->setOneTimeUse($oneTimeUse);

        $this->repo->saveOrFail($receiver);
    }

    protected function buildBankAccount($merchant)
    {
        $ba = new BankAccount\Entity;

        $baInput = $this->defaultVirtualBankAccountInput($merchant);

        $ba = $ba->build($baInput);

        $ba->getValidator()->validateIfscCode();

        $ba->merchant()->associate($merchant);

        return $ba;
    }

    protected function defaultVirtualBankAccountInput($merchant)
    {
        $provider = $this->selectProvider($merchant);

        $details = Provider::ACCOUNT_DETAILS[$provider];

        $merchantDetails = [
            BankAccount\Entity::ACCOUNT_NUMBER     => $this->generateAccountNumberForProvider($provider),
            BankAccount\Entity::BENEFICIARY_NAME   => $merchant->getBillingLabelElseName() . ' Virtual Account',
            BankAccount\Entity::BENEFICIARY_EMAIL  => $merchant->getTransactionReportEmail()[0],
            BankAccount\Entity::BENEFICIARY_MOBILE => 9876543210//$merchant->merchantDetail->getContactMobile(),
        ];

        return array_merge($details, $merchantDetails);
    }

    protected function selectProvider($merchant)
    {
        return Provider::YES_BANK;
    }

    protected function generateAccountNumberForProvider($provider)
    {
        $master = Provider::MASTER[$provider];

        $timestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $accountNumber = $master . $timestamp;

        $digits = self::ACCOUNT_NUMBER_LENGTH - strlen($accountNumber);

        $accountNumber .= rand(pow(10, $digits-1), pow(10, $digits)-1);

        return $accountNumber;
    }
}
