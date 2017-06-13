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

            $this->buildReceivers($input);

            $this->repo->saveOrFail($this->virtualAccount);
        });

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_CREATED,
            $this->virtualAccount->toArrayPublic()
        );

        return $this->virtualAccount->toArrayPublic();
    }

    public function getVirtualAccount(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchantIdWithRelations(
                                $id,
                                $this->merchant,
                                ['bankAccount']
                               );

        return $virtualAccount->toArrayPublic();
    }

    public function getVirtualAccounts(array $input)
    {
        $virtualAccounts = $this->repo->virtual_account
                                ->fetch($input, $this->merchant->getId());

        return $virtualAccounts->toArrayPublic();
    }

    public function editVirtualAccount(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchant($id, $this->merchant);

        $virtualAccount = $this->core->edit($virtualAccount, $input);

        return $virtualAccount->toArrayPublic();
    }

    public function deleteVirtualAccount(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchant($id, $this->merchant);

        $this->repo->deleteOrFail($virtualAccount);

        return $virtualAccount->toArrayDeleted();
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
        if (empty($input[Entity::RECEIVER_TYPE]) === true)
        {
            $input[Entity::RECEIVER_TYPE] = self::DEFAULT_RECEIVER_TYPE;
        }

        if (is_array($input[Entity::RECEIVER_TYPE]) === false)
        {
            $input[Entity::RECEIVER_TYPE] = [$input[Entity::RECEIVER_TYPE]];
        }
    }

    protected function buildReceivers(array $input)
    {
        $name = $input[Entity::NAME] ?? null;

        $descriptor = $input[Entity::DESCRIPTOR] ?? null;

        $receiverHelper = new Receiver($this->merchant, $name, $descriptor);

        foreach ($input[Entity::RECEIVER_TYPE] as $receiverType)
        {
            $func = 'build' . studly_case($receiverType);

            $receiver = $receiverHelper->$func();

            $association = camel_case($receiverType);

            $this->virtualAccount->$association()->associate($receiver);
        }
    }
}
