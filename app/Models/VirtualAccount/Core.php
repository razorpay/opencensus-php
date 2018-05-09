<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\BharatQr\Constants;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Customer\Entity as Customer;

class Core extends Base\Core
{
    public function create(
        array $input,
        Merchant $merchant,
        Customer $customer = null,
        Order $order = null,
        bool $shared = false): Entity
    {
        $virtualAccount = $this->createEntityAndAssociate($merchant);

        $virtualAccount = $this->repo->transaction(function() use ($virtualAccount, $input, $customer, $order, $shared)
        {
            $virtualAccount->build($input);

            if ($shared === true)
            {
                $virtualAccount->setId(Entity::SHARED_VIRTUAL_ACCOUNT);
            }

            $this->validateDescriptor($virtualAccount);

            $virtualAccount->customer()->associate($customer);

            $virtualAccount->associateOrder($order);

            $this->buildReceivers($virtualAccount, $input[Entity::RECEIVERS]);

            $this->repo->saveOrFail($virtualAccount);

            return $virtualAccount;
        });

        $this->eventVirtualAccountCreated($virtualAccount);

        return $virtualAccount;
    }

    /**
     * A static qr code for all the unexpected payments is picked.
     *
     * @param Merchant $merchant
     */
    public function createOrFetchSharedVirtualAccount(Merchant $merchant)
    {
        $virtualAccountId = Entity::SHARED_VIRTUAL_ACCOUNT;

        $virtualAccount = $this->repo->virtual_account->find($virtualAccountId);

        if ($virtualAccount === null)
        {
            $virtualAccount = $this->createSharedVirtualAccount($merchant);
        }

        return $virtualAccount;
    }

    protected function createSharedVirtualAccount(Merchant $merchant)
    {
        $customers = $this->repo->customer->fetchByMerchantId($merchant->getId());

        $input = [
            Entity::RECEIVERS => [
                Entity::TYPES => [Receiver::QR_CODE, Receiver::BANK_ACCOUNT]
            ],
        ];

        return $this->create($input, $merchant, $customers[0], null, true);
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
        $receiverHelper = $virtualAccount->getReceiverBuilder();

        foreach ($receivers[Entity::TYPES] as $receiverType)
        {
            $options = $receivers[$receiverType] ?? [];

            $this->validateReceiver($receiverType, $virtualAccount);

            $func = 'build' . studly_case($receiverType);

            $receiver = $receiverHelper->$func($virtualAccount, $options);

            $association = camel_case($receiverType);

            $virtualAccount->$association()->associate($receiver);
        }
    }

    protected function validateReceiver(string $receiver, Entity $virtualAccount)
    {
        switch ($receiver)
        {
            case Receiver::BANK_ACCOUNT:
                $this->verifyBankTransferEnabled($virtualAccount->merchant);
                break;

            case Receiver::QR_CODE:
                $this->verifyBharatQrEnabled($virtualAccount->merchant);
                break;

            default:
                // We don't throw exception here
                // because receiver is already validated
                // and we don't want to put any validation
                // for receiver being enabled by default
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

        // Removing the below check for crypto merchants so that they can
        // create new VAs with the same descriptor, using a different provider.
        // Default provider for crypto merchants has already been changed.
        if ($virtualAccount->merchant->isCategory2Cryptocurrency() === true)
        {
            return;
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

    protected function verifyBankTransferEnabled(Merchant $merchant)
    {
        $merchantMethods = $this->getMethodsForMerchant($merchant);

        if (($merchantMethods === null) or
            ($merchantMethods->isBankTransferEnabled() === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_BANK_TRANSFER_NOT_ENABLED_FOR_MERCHANT);
        }
    }

    protected function verifyBharatQrEnabled(Merchant $merchant)
    {
        $feature = Feature\Constants::BHARAT_QR;

        if ($merchant->isFeatureEnabled($feature) === false)
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

    public function eventVirtualAccountCredited(Payment $payment)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $payment
        ];

        $this->app['events']->fire('api.virtual_account.credited', $eventPayload);
    }

    public function eventVirtualAccountCreated(Entity $virtualAccount)
    {
        $eventPayload = [
            ApiEventSubscriber::MAIN => $virtualAccount
        ];

        $this->app['events']->fire('api.virtual_account.created', $eventPayload);
    }
}
