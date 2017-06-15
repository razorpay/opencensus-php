<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class Service extends Base\Service
{
    protected $core;

    const DEFAULT_RECEIVER_TYPE = Receiver::BANK_ACCOUNT;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::VIRTUAL_ACCOUNT_CREATE_REQUEST, $input);

        $this->verifyMerchantIsLiveForLiveRequest();

        $this->verifyBankTransferEnabled();

        $customer = $this->getCustomerIfGiven($input);

        $this->setDefaultReceiverTypeIfNeeded($input);

        $this->repo->transaction(function() use ($input, $customer)
        {
            $this->virtualAccount = $this->core->create($input, $this->merchant, $customer);

            $this->buildReceivers($this->virtualAccount, $input[Entity::RECEIVER_TYPE]);

            $this->repo->saveOrFail($this->virtualAccount);
        });

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_CREATED,
            $this->virtualAccount->toArrayPublic()
        );

        return $this->virtualAccount->toArrayPublic();
    }

    public function getSingle(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchantWithRelations(
                                $id,
                                $this->merchant,
                                ['bankAccount']
                               );

        return $virtualAccount->toArrayPublic();
    }

    public function getMultiple(array $input)
    {
        $virtualAccounts = $this->repo->virtual_account
                                ->fetch($input, $this->merchant->getId());

        return $virtualAccounts->toArrayPublic();
    }

    public function edit(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchant($id, $this->merchant);

        $virtualAccount = $this->core->edit($virtualAccount, $input);

        return $virtualAccount->toArrayPublic();
    }

    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $virtualAccount = $this->repo->virtual_account
                               ->findByIdAndMerchant($id, $this->merchant);

        $this->repo->deleteOrFail($virtualAccount);

        return $virtualAccount->toArrayDeleted();
    }

    protected function getCustomerIfGiven(array $input)
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

    protected function buildReceivers(Entity $virtualAccount, array $receiverTypes)
    {
        $name = $virtualAccount->getName();

        $descriptor = $virtualAccount->getDescriptor();

        $receiverHelper = new Receiver($this->merchant, $name, $descriptor);

        foreach ($receiverTypes as $receiverType)
        {
            $func = 'build' . studly_case($receiverType);

            $receiver = $receiverHelper->$func($virtualAccount);

            $association = camel_case($receiverType);

            $this->virtualAccount->$association()->associate($receiver);
        }
    }

    protected function verifyMerchantIsLiveForLiveRequest()
    {
        // On live request, ensure that merchant isn't blocked temporarily
        if (($this->mode === Mode::LIVE) and
            ($this->merchant->isLive() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED);
        }
    }

    protected function verifyBankTransferEnabled()
    {
        $merchantMethods = $this->getMethodsForMerchant($this->merchant);

        if (($merchantMethods === null) or
            ($merchantMethods->isBankTransferEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function getMethodsForMerchant(Merchant\Entity $merchant)
    {
        if ($merchant->hasRelation('methods') === false)
        {
            $methods = $this->repo->methods->getMethodsForMerchant($merchant);
        }

        return $merchant->methods;
    }
}
