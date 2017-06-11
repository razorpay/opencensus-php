<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    const DEFAULT_RECEIVER_TYPE = Receiver::BANK_ACCOUNT;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function createVirtualAccount(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_REQUEST, $input);

        $customer = $this->setCustomerIfGiven($input);

        $this->setDefaultReceiverTypeIfNeeded($input);

        $this->repo->transaction(function() use ($input, $customer)
        {
            $this->virtualAccount = $this->core->create($input, $this->merchant, $customer);

            // Make receivers
        });

        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATED, $this->virtualAccount->toArray());

        return $this->virtualAccount->toArrayPublic();
    }

    public function getVirtualAccount(string $id)
    {
        $virtualAccount = $this->repo->virtual_account->findOrFailPublic($id);

        return $virtualAccount->toArrayPublic();
    }

    public function getVirtualAccounts(array $input)
    {
        $virtualAccounts = $this->repo->virtual_account->fetch($input, $this->merchant);

        return $virtualAccounts->toArrayPublic();
    }

    public function closeVirtualAccount(string $id)
    {
        $virtualAccount = $this->repo->virtual_account->findOrFailPublic($id);

        $virtualAccount->setStatus(Status::CLOSED);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount->toArrayPublic();
    }

    protected function setCustomerIfGiven(array $input)
    {
        $customer = null;

        if (isset($input[Entity::CUSTOMER_ID]) === true)
        {
            $customerId = $input[Entity::CUSTOMER_ID];

            $customer = $this->repo->customer
                             ->findByPublicIdAndMerchant($customerId, $this->merchant);
        }

        return $customer;
    }

    protected function setDefaultReceiverTypeIfNeeded(array & $input)
    {
        if(isset($input[Entity::RECEIVER_TYPE]) === false)
        {
            $input[Entity::RECEIVER_TYPE] = self::DEFAULT_RECEIVER_TYPE;
        }
    }
}
