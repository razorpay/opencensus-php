<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Customer\Entity as Customer;

class Core extends Base\Core
{
    public function create(array $input, Merchant $merchant, Customer $customer = null): Entity
    {
        $virtualAccount = $this->createEntityAndAssociate($merchant);

        $virtualAccount = $this->repo->transaction(function() use ($virtualAccount, $input, $customer)
        {
            $virtualAccount->build($input);

            $this->validateDescriptor($virtualAccount);

            $virtualAccount->customer()->associate($customer);

            $this->buildReceivers($virtualAccount, $input[Entity::RECEIVERS]);

            $this->repo->saveOrFail($virtualAccount);

            return $virtualAccount;
        });

        return $virtualAccount;
    }

    public function createWithoutReceivers(array $input, Merchant $merchant)
    {
        $virtualAccount = $this->createEntityAndAssociate($merchant);

        $virtualAccount->build($input);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    protected function createEntityAndAssociate(Merchant $merchant)
    {
        $virtualAccount = (new Entity)->generateId();

        // We associate the merchant before building the entity, as
        // merchant billing label is used to modify the name attribute
        $virtualAccount->merchant()->associate($merchant);

        return $virtualAccount;
    }

    protected function buildReceivers(Entity $virtualAccount, array $receivers)
    {
        $name = $virtualAccount->getName();

        $receiverHelper = new Receiver($virtualAccount->merchant, $name);

        foreach ($receivers[Entity::TYPES] as $receiverType)
        {
            $options = $receivers[$receiverType] ?? [];

            $this->validateReceiver($receiverType);

            $func = 'build' . studly_case($receiverType);

            $receiver = $receiverHelper->$func($virtualAccount, $options);

            $association = camel_case($receiverType);

            $virtualAccount->$association()->associate($receiver);
        }
    }

    protected function validateReceiver(string $receiver)
    {
        switch ($receiver)
        {
            case Receiver::BANK_ACCOUNT:
                $this->verifyBankTransferEnabled();
                break;

            case Receiver::QR_CODE:
                $this->verifyBharatQrEnabled();
                break;

            default:
                return;
        }
    }

    public function edit(Entity $virtualAccount, array $input)
    {
        $virtualAccount->edit($input);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    protected function validateDescriptor(Entity $virtualAccount)
    {
        if ($virtualAccount->getDescriptor() === null)
        {
            return;
        }

        if ($virtualAccount->merchant->getHandle() === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DESCRIPTOR_SANS_HANDLE);
        }

        $existingVirtualAccounts = $this->repo->virtual_account
                                        ->findActiveByDescriptorAndMerchant(
                                            $virtualAccount->getDescriptor(),
                                            $virtualAccount->merchant);

        if ($existingVirtualAccounts->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_IDENTICAL_DESCRIPTOR,
                'descriptor',
                [
                    'existing_ids' => $existingVirtualAccounts->getIds(),
                    'descriptor'   => $virtualAccount->getDescriptor(),
                ]);
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

    protected function verifyBharatQrEnabled()
    {
        $feature = Feature\Constants::BHARAT_QR;

        if ($this->merchant->isFeatureEnabled($feature) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BHARAT_QR_NOT_ENABLED_FOR_MERCHANT);
        }

    }

    protected function getMethodsForMerchant(Merchant $merchant)
    {
        if ($merchant->hasRelation('methods') === false)
        {
            $methods = $this->repo->methods->getMethodsForMerchant($merchant);
        }

        return $merchant->methods;
    }
}
