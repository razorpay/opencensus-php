<?php

namespace RZP\Models\SubscriptionRegistration;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Customer;
use RZP\Trace\TraceCode;
use RZP\Models\BankAccount;

class Core extends Base\Core
{
    protected $subscriptionRegistration;

    protected $customer;

    protected $invoice;

    protected $batch;

    protected $bankAccount;

    public function create(array $input, Merchant\Entity $merchant, Customer\Entity $customer): Entity
    {
        $this->trace->info(TraceCode::SUBSCRIPTION_REGISTRATION_CREATE_REQUEST, $input);

        $subscriptionRegistration = (new Entity)->build($input);

        $subscriptionRegistration->merchant()->associate($merchant);

        $subscriptionRegistration->customer()->associate($customer);

        $this->repo->saveOrFail($subscriptionRegistration);

        return $subscriptionRegistration;
    }

    public function createAuthLink(array $input, Merchant\Entity $merchant, Batch\Entity $batch = null): Invoice\Entity
    {
        $this->merchant = $merchant;

        $this->repo->transaction(
            function() use ($input, $batch)
            {
                $this->batch = $batch;

                $this->createCustomer($input);

                $this->createSubscriptionRegistration($input);

                $this->createInvoice($input);
            });

        return $this->invoice;
    }

    public function createSubscriptionRegistration(array & $input)
    {
        if (isset($input[Constants\Entity::SUBSCRIPTION_REGISTRATION]) === true)
        {
            $subrInput = array_pull($input, Constants\Entity::SUBSCRIPTION_REGISTRATION);

            $bankInput = [];

            $bankName = null;

            if (array_key_exists(Constants\Entity::BANK_ACCOUNT, $subrInput))
            {
                $bankInput = array_pull($subrInput, Constants\Entity::BANK_ACCOUNT);

                if (array_key_exists(BankAccount\Entity::BANK_NAME, $bankInput))
                {
                    $bankName = array_pull($bankInput, BankAccount\Entity::BANK_NAME);
                }
            }

            $this->subscriptionRegistration = $this->create($subrInput, $this->merchant, $this->customer);

            if (empty($bankInput) === false)
            {
                $this->setDefaultValuesForBank($bankInput);

                $bankAccountCore = new BankAccount\Core();

                $bankAccount = $bankAccountCore->addOrUpdateBankAccountForCustomer($bankInput, $this->customer);

                $this->bankAccount = $bankAccount;

                $this->setBankAccountEntity($this->subscriptionRegistration, $bankAccount);

            }
            if (empty($bankName) === false)
            {
                $this->subscriptionRegistration->setBank($bankName);
            }
        }
    }

    public function createCustomer(array & $input)
    {
        $details = array_pull($input, Constants\Entity::CUSTOMER);

        $this->customer = (new Customer\Core)->createLocalCustomer($details, $this->merchant, false);

        $input[Entity::CUSTOMER_ID] = $this->customer->getPublicId();
    }

    public function createInvoice(array & $input)
    {
        $invoiceCore = new Invoice\Core();

        $this->invoice = $invoiceCore->create(
            $input,
            $this->merchant,
            null,
            $this->batch,
            $this->subscriptionRegistration);

        if ($this->subscriptionRegistration !== null)
        {
            $this->invoice->entity()->associate($this->subscriptionRegistration);

            $this->repo->saveOrFail($this->invoice);
        }
    }

    public function setBankAccountEntity(Entity $subscriptionRegistration, BankAccount\Entity $bankAccount)
    {
        $subscriptionRegistration->entity()->associate($bankAccount);

        $this->repo->saveOrFail($subscriptionRegistration);
    }

    protected function setDefaultValuesForBank(array & $bankInput)
    {
        if (array_key_exists(BankAccount\Entity::BENEFICIARY_EMAIL, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_EMAIL] = $this->customer->getEmail();
        }

        if (array_key_exists(BankAccount\Entity::BENEFICIARY_MOBILE, $bankInput) == false)
        {
            $bankInput[BankAccount\Entity::BENEFICIARY_MOBILE] = $this->customer->getContact();
        }
    }
}
