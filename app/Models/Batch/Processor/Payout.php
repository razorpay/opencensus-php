<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Payout as PayoutModel;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Batch;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers\Payout as Helper;

class Payout extends Base
{
    /**
     * @var Payout\Core
     */
    protected $payoutCore;

    /**
     * @var Customer\Core
     */
    protected $customerCore;

    public function __construct(Batch\Entity $batch)
    {
        parent::__construct($batch);

        $this->payoutCore = new PayoutModel\Core;

        $this->bankAccountCore = new BankAccount\Core;

        $this->customerCore = new Customer\Core;
    }

    protected function processEntry(array & $entry)
    {
        $customer = $this->createCustomer($entry);

        $bankAccount = $this->createBankAccount($entry, $customer);

        $payout = $this->createPayout($entry, $bankAccount, $customer);

        $entry[Header::STATUS] = Batch\Status::SUCCESS;
    }

    protected function createCustomer(array & $entry)
    {
        $customerCreateInput = Helper::getCustomerCreateInput($entry);

        $customer = $this->customerCore->createLocalCustomer($customerCreateInput, $this->merchant, false);

        $entry[Header::PAYOUT_CUSTOMER_ID] = $customer->getPublicId();

        return $customer;
    }

    protected function createBankAccount(array & $entry, Customer\Entity $customer)
    {
        $bankAccount = new BankAccount\Entity;

        $bankAccountCreateInput = Helper::getBankAccountCreateInput($entry);

        $bankAccount = $bankAccount->build($bankAccountCreateInput, 'addPayoutDestination');

        $bankAccount->merchant()->associate($this->merchant);

        $bankAccount->associateCustomer($customer);

        $this->repo->saveOrFail($bankAccount);

        $entry[Header::PAYOUT_BANK_ACCOUNT_ID] = $bankAccount->getPublicId();

        return $bankAccount;
    }

    protected function createPayout(array & $entry, BankAccount\Entity $bankAccount, Customer\Entity $customer)
    {
        $payoutCreateInput = Helper::getPayoutCreateInput($entry, $bankAccount, $customer);

        $payout = $this->payoutCore->directPayout($payoutCreateInput, $this->merchant);

        $entry[Header::PAYOUT_ID]          = $payout->getPublicId();
        $entry[Header::PAYOUT_FEE]         = $payout->getFee();
        $entry[Header::PAYOUT_TAX] = $payout->getTax();

        return $payout;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
