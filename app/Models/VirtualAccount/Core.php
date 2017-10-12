<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Customer\Entity as Customer;

class Core extends Base\Core
{
    public function create(array $input, Merchant $merchant, Customer $customer = null): Entity
    {
        $virtualAccount = (new Entity);

        // We associate the merchant before building the entity, as
        // merchant billing label is used to modify the name attribute
        $virtualAccount->merchant()->associate($merchant);

        $virtualAccount->build($input);

        $this->validateDescriptor($virtualAccount, $merchant);

        $virtualAccount->customer()->associate($customer);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    public function edit(Entity $virtualAccount, array $input)
    {
        $virtualAccount->edit($input);

        $this->repo->saveOrFail($virtualAccount);

        return $virtualAccount;
    }

    protected function validateDescriptor(Entity $virtualAccount, Merchant $merchant)
    {
        if ($virtualAccount->getDescriptor() === null)
        {
            return;
        }

        if ($merchant->getHandle() === null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_DESCRIPTOR_SANS_HANDLE);
        }

        $existingVirtualAccounts = $this->repo->virtual_account
                                        ->findActiveByDescriptorAndMerchant(
                                            $virtualAccount->getDescriptor(),
                                            $merchant);

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
}
