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
        $this->repo->transaction(function() use ($customer)
        {
            $bankAccount = $this->buildBankAccount($this->merchant);

            $bankAccount->associateCustomer($customer);

            $bankAccount->setVirtual(true);

            $this->createReceiverFromBankAccount($bankAccount);

            $this->repo->saveOrFail($bankAccount);
        });

        return $bankAccount;
    }

    public function addStandingBankAccount()
    {
        $bankAccount = $this->buildBankAccount($this->merchant);

        $bankAccount->associateMerchant($this->merchant);

        $bankAccount->setVirtual(true);

        $this->createReceiverFromBankAccount($bankAccount, false);

        $this->repo->saveOrFail($bankAccount);

        return $bankAccount;
    }

    protected function createReceiverFromBankAccount($bankAccount, $singleUse = true)
    {
        $receiver = new Entity;

        $receiver->entityAssociate($bankAccount);

        $receiver->setSingleUse($singleUse);

        $this->repo->saveOrFail($receiver);
    }

    protected function buildBankAccount($merchant)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountInput = $this->generateVirtualBankAccountInput($merchant);

        $bankAccount = $bankAccount->build($bankAccountInput);

        $bankAccount->getValidator()->validateIfscCode();

        $bankAccount->merchant()->associate($merchant);

        return $bankAccount;
    }

    protected function generateVirtualBankAccountInput($merchant)
    {
        $provider = $this->selectProvider($merchant);

        $details = Provider::DEFAULT_DETAILS[$provider];

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

    /**
     * Generates unique account number for a given provider
     *
     * Each provider has a pre-decided 'master' or prefix that must be used.
     * Max length of account number is 20 characters. We use the timestamp
     * in seconds, followed by random digits to pad.
     *
     * So if YesBank is giving us a master of length 6, and timestamps are
     * currently 10 digits long, this logic allows us to generate ~10000
     * unique numbers every second.
     *
     * @param  string $provider Descripter for provider of Virtual a/c services
     * @return string Unique account number
     */
    protected function generateAccountNumberForProvider($provider)
    {
        $master = Provider::MASTER[$provider];

        $timestamp = Carbon::now('Asia/Kolkata')->getTimestamp();

        $accountNumber = $master . $timestamp;

        $digits = self::ACCOUNT_NUMBER_LENGTH - strlen($accountNumber);

        $accountNumber .= rand(pow(10, $digits - 1), pow(10, $digits) - 1);

        return $accountNumber;
    }
}
