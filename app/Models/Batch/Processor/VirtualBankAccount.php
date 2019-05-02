<?php

namespace RZP\Models\Batch\Processor;

use RZP\Base\RuntimeManager;
use RZP\Models\Batch\Entity;
use RZP\Models\Batch\Header;
use RZP\Models\VirtualAccount;
use RZP\Models\Customer as Customer;
use RZP\Models\Batch\Helpers\VirtualBankAccount as Helper;

class VirtualBankAccount extends Base
{
    /**
     * @var VirtualAccount\Core
     */
    protected $virtualAccountCore;

    /**
     * @var Customer\Core
     */
    protected $customerCore;

    public function __construct(Entity $batch)
    {
        parent::__construct($batch);

        $this->virtualAccountCore = new VirtualAccount\Core;

        $this->customerCore = new Customer\Core;
    }

    protected function processEntry(array & $entry)
    {
        $customer = $this->createCustomer($entry);

        $virtualAccount = $this->createVirtualAccount($entry, $customer);

        $entry[Header::STATUS] = $virtualAccount->getStatus();
    }

    protected function createCustomer(array & $entry)
    {
        if (empty($entry[Header::VA_CUSTOMER_ID]) === false)
        {
            $customerId = $entry[Header::VA_CUSTOMER_ID];

            $customer = $this->repo
                             ->customer
                             ->findbyPublicIdAndMerchant($customerId, $this->merchant);

            return $customer;
        }

        $customerCreateInput = Helper::getCustomerCreateInput($entry);

        $customer = $this->customerCore->createLocalCustomer($customerCreateInput, $this->merchant, false);

        $entry[Header::VA_CUSTOMER_ID] = $customer->getPublicId();

        return $customer;
    }

    protected function createVirtualAccount(array & $entry, Customer\Entity $customer)
    {
        $virtualAccountCreateInput = Helper::getVirtualAccountCreateInput($entry, $customer);

        $virtualAccount = $this->virtualAccountCore->create($virtualAccountCreateInput, $this->merchant, $customer);

        $entry[Header::VA_ID] = $virtualAccount->getPublicId();

        $bankAccount = $virtualAccount->bankAccount;

        $entry[Header::VA_BANK_ACCOUNT_ID]     = $bankAccount->getPublicId();
        $entry[Header::VA_BANK_ACCOUNT_NAME]   = $bankAccount->getName();
        $entry[Header::VA_BANK_ACCOUNT_NUMBER] = $bankAccount->getAccountNumber();
        $entry[Header::VA_BANK_ACCOUNT_IFSC]   = $bankAccount->getIfscCode();

        return $virtualAccount;
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }

    protected function increaseAllowedSystemLimits()
    {
        RuntimeManager::setMemoryLimit('1024M');

        RuntimeManager::setTimeLimit(900);

        RuntimeManager::setMaxExecTime(900);
    }
}
