<?php

namespace RZP\Tests\P2p\Service\Base\Traits;

use Carbon\Carbon;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Transaction\Mode;
use RZP\Models\P2p\Transaction\Flow;
use RZP\Models\P2p\Transaction\Type;
use RZP\Models\P2p\Transaction\Action;
use RZP\Models\P2p\Transaction\Entity;
use RZP\Models\P2p\Transaction\Status;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Tests\P2p\Service\Base\Fixtures\Fixtures;

/**
 * @property Fixtures $fixtures
 *
 * Trait TransactionTrait
 * @package RZP\Tests\P2p\Service\Base\Traits
 */
trait TransactionTrait
{
    public function createCompletedPayTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::STATUS              => Status::COMPLETED,
            Entity::INTERNAL_STATUS     => Status::COMPLETED,
        ];

        $upiDefaults = [
            UpiTransaction\Entity::GATEWAY_ERROR_CODE       => '00',
        ];

        return $this->createPayTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createFailedPayTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::STATUS              => Status::FAILED,
            Entity::INTERNAL_STATUS     => Status::FAILED,
        ];

        $upiDefaults = [
            UpiTransaction\Entity::GATEWAY_ERROR_CODE   => 'XY',
        ];

        return $this->createPayTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createPayTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::TYPE                => Type::PAY,
            Entity::FLOW                => Flow::DEBIT,
            Entity::PAYER_TYPE          => Vpa\Entity::VPA,
            Entity::PAYER_ID            => $this->fixtures->vpa(self::DEVICE_1)->getId(),
            Entity::PAYEE_TYPE          => Vpa\Entity::VPA,
            Entity::PAYEE_ID            => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::BANK_ACCOUNT_ID     => $this->fixtures->vpa(self::DEVICE_1)->getBankAccountId(),
            Entity::STATUS              => Status::CREATED,
            Entity::INTERNAL_STATUS     => Status::CREATED,
        ];

        $upiDefaults = [
            UpiTransaction\Entity::ACTION                   => Action::INITIATE_PAY,
            UpiTransaction\Entity::STATUS                   => Status::CREATED,
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => str_random(35),
            UpiTransaction\Entity::RRN                      => random_integer(11),
        ];

        return $this->createTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createCollectPendingTransaction(): Entity
    {
        return $this->createCollectTransaction([
            Entity::STATUS              => Status::PENDING,
            Entity::INTERNAL_STATUS     => Status::PENDING,
        ], [
            UpiTransaction\Entity::GATEWAY_ERROR_CODE           => 'BT',
            UpiTransaction\Entity::GATEWAY_ERROR_DESCRIPTION    => 'Transaction pending',
        ]);
    }

    public function createCollectTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::TYPE                => Type::COLLECT,
            Entity::FLOW                => Flow::CREDIT,
            Entity::PAYER_TYPE          => Vpa\Entity::VPA,
            Entity::PAYER_ID            => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::PAYEE_TYPE          => Vpa\Entity::VPA,
            Entity::PAYEE_ID            => $this->fixtures->vpa(self::DEVICE_1)->getId(),
            Entity::BANK_ACCOUNT_ID     => $this->fixtures->vpa(self::DEVICE_1)->getBankAccountId(),
            Entity::STATUS              => Status::INITIATED,
            Entity::INTERNAL_STATUS     => Status::INITIATED,
        ];

        $upiDefaults = [
            UpiTransaction\Entity::ACTION                   => Action::INITIATE_COLLECT,
            UpiTransaction\Entity::STATUS                   => Status::COMPLETED,
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => str_random(35),
            UpiTransaction\Entity::RRN                      => random_integer(11),
        ];

        return $this->createTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createPayIncomingTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::TYPE                => Type::PAY,
            Entity::FLOW                => Flow::CREDIT,
            Entity::PAYER_TYPE          => Vpa\Entity::VPA,
            Entity::PAYER_ID            => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::PAYEE_TYPE          => Vpa\Entity::VPA,
            Entity::PAYEE_ID            => $this->fixtures->vpa(self::DEVICE_1)->getId(),
            Entity::BANK_ACCOUNT_ID     => $this->fixtures->vpa(self::DEVICE_1)->getBankAccountId(),
            Entity::STATUS              => Status::COMPLETED,
            Entity::INTERNAL_STATUS     => Status::COMPLETED,
        ];

        $upiDefaults = [
            UpiTransaction\Entity::ACTION                   => Action::INCOMING_PAY,
            UpiTransaction\Entity::STATUS                   => Status::COMPLETED,
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => str_random(35),
            UpiTransaction\Entity::RRN                      => random_integer(11),
        ];

        return $this->createTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createCollectIncomingTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::TYPE                => Type::COLLECT,
            Entity::FLOW                => Flow::DEBIT,
            Entity::PAYER_TYPE          => Vpa\Entity::VPA,
            Entity::PAYER_ID            => $this->fixtures->vpa(self::DEVICE_1)->getId(),
            Entity::PAYEE_TYPE          => Vpa\Entity::VPA,
            Entity::PAYEE_ID            => $this->fixtures->vpa(self::DEVICE_2)->getId(),
            Entity::BANK_ACCOUNT_ID     => $this->fixtures->vpa(self::DEVICE_1)->getBankAccountId(),
            Entity::STATUS              => Status::REQUESTED,
            Entity::INTERNAL_STATUS     => Status::REQUESTED,
            Entity::EXPIRE_AT           => Carbon::now()->addDays(3)->getTimestamp(),
        ];

        $upiDefaults = [
            UpiTransaction\Entity::ACTION                   => Action::INCOMING_COLLECT,
            UpiTransaction\Entity::STATUS                   => Status::CREATED,
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => str_random(35),
            UpiTransaction\Entity::RRN                      => random_integer(11),
        ];

        return $this->createTransaction(array_merge($defaults, $attributes), array_merge($upiDefaults, $upi));
    }

    public function createTransaction(array $attributes = [], array $upi = []): Entity
    {
        $defaults = [
            Entity::METHOD              => 'upi',
            Entity::AMOUNT              => 1000,
            Entity::CURRENCY            => 'INR',
            Entity::DESCRIPTION         => 'Test Transaction',
            Entity::GATEWAY             => $this->gateway,
            Entity::MODE                => Mode::DEFAULT,
            Entity::MERCHANT_ID         => $this->fixtures->device(self::DEVICE_1)->getMerchantId(),
            Entity::CUSTOMER_ID         => $this->fixtures->device(self::DEVICE_1)->getCustomerId(),
            Entity::DEVICE_ID           => $this->fixtures->device(self::DEVICE_1)->getId(),
            Entity::HANDLE              => $this->fixtures->handle->getCode(),
        ];

        $entity = new Entity();
        $entity->forceFill(array_filter(array_merge($defaults, $attributes)));
        $entity->saveOrFail();

        $this->createUpiTransaction($entity, $upi);

        return $entity->refresh();
    }

    public function createUpiTransaction(Entity $transaction, array $attributes = [])
    {
        $defaults = [
            UpiTransaction\Entity::GATEWAY_DATA => ['id' => $transaction->getId()],
            UpiTransaction\Entity::REF_ID       => $transaction->getId(),
            UpiTransaction\Entity::DEVICE_ID    => $this->fixtures->device(self::DEVICE_1)->getId(),
            UpiTransaction\Entity::HANDLE       => $this->fixtures->handle(self::DEVICE_1)->getCode(),
        ];

        $entity = new UpiTransaction\Entity();
        $entity->forceFill(array_filter(array_merge($defaults, $attributes)));
        $entity->associateTransaction($transaction);
        $entity->saveOrFail();

        $transaction->upi()->setModel($transaction);

        return $entity;
    }

}
