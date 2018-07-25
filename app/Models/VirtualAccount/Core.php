<?php

namespace RZP\Models\VirtualAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Customer;
use RZP\Models\Merchant\Account;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Merchant\Entity as Merchant;

class Core extends Base\Core
{
    const VA_BANK_ACCOUNT_GENERATION = 'va_bank_account_generation';

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(
        array $input,
        Merchant $merchant,
        Customer\Entity $customer = null,
        Order $order = null): Entity
    {
        //
        // VA creation is a bit broken at the moment. Creation requires multiple entities (VA+receivers)
        // to be committed to the DB, but while building receivers we also need to take a lock on the
        // generated account number and do a DB query to check for uniqueness. This will require a
        // refactor to be solved.
        //
        // For now, we're simply adding a global lock on VA creation to avoid duplicates being created.
        //
        $virtualAccount = $this->mutex->acquireAndRelease(
            self::VA_BANK_ACCOUNT_GENERATION,
            function() use ($input, $merchant, $customer, $order)
            {
                $virtualAccount = $this->createEntityAndAssociate($merchant);

                return $this->buildVirtualAccountAndReceivers($virtualAccount, $input, $customer, $order);
            },
            // The entire VA creation process inside this lock actually takes
            // an avg of 10ms, so 1000x i.e. 10 seconds is more than adequate TTL
            10,
            ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS,
            // A process will generally not need to do multiple retries at all,
            // since the retry times are adequate for the previous process to complete.
            2,
            // 2x and 4x of avg response time for this entire route (not just the process within the lock)
            200,
            400);

        return $virtualAccount;
    }

    /**
     * A static qr code for all the unexpected payments is picked.
     */
    public function createOrFetchSharedVirtualAccount()
    {
        $virtualAccountId = Entity::SHARED_ID;

        $virtualAccount = $this->repo->virtual_account->find($virtualAccountId);

        if ($virtualAccount === null)
        {
            $virtualAccount = $this->createSharedVirtualAccount();
        }

        return $virtualAccount;
    }

    protected function buildVirtualAccountAndReceivers(
        Entity $virtualAccount,
        array $input,
        Customer\Entity $customer = null,
        Order $order = null): Entity
    {
        $virtualAccount = $this->repo->transaction(function() use ($virtualAccount, $input, $customer, $order)
        {
            $virtualAccount->build($input);

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

    protected function createSharedVirtualAccount()
    {
        $sharedMerchantId = $this->getDefaultMerchantId();

        $merchant = $this->repo->merchant->find($sharedMerchantId);

        $customer = (new Customer\Core)->createOrFetchSharedCustomer($merchant);

        $virtualAccount = (new Entity)->setId(Entity::SHARED_ID);

        $virtualAccount->merchant()->associate($merchant);

        $input = [
            Entity::RECEIVERS => [
                Entity::TYPES => [
                    Receiver::QR_CODE,
                    Receiver::BANK_ACCOUNT
                ]
            ],
        ];

        return $this->buildVirtualAccountAndReceivers($virtualAccount, $input, $customer, null);
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

    /**
     * For unexpected payments, we use the demo page merchant. This merchant only
     * exists on prod. For other envs, we use the test merchant, i.e. '10000000000000'.
     */
    protected function getDefaultMerchantId()
    {
        $defaultMerchantId = Account::DEMO_PAGE_ACCOUNT;

        if ($this->env !== 'production')
        {
            $defaultMerchantId = Account::TEST_ACCOUNT;
        }

        return $defaultMerchantId;
    }
}
