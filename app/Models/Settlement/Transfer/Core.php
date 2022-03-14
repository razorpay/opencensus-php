<?php

namespace RZP\Models\Settlement\Transfer;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\Merchant\Balance;
use RZP\Models\Currency\Currency;
use RZP\Models\Settlement\Status;
use RZP\Models\Settlement\Destination;
use RZP\Models\Settlement\Entity as SettlementEntity;
use RZP\Exception\BadRequestValidationFailureException;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * This will create internal transfer to the parent merchant
     * - create settlement transfer for destination merchant
     * - create a credit transaction for destination merchant
     * - mark settlement as processed
     *
     * @param SettlementEntity $settlement
     * @param string $destinationMerchantId
     * @param string $balanceType
     *
     * @return Entity
     *
     * @throws BadRequestValidationFailureException
     */
    public function transfer(
        SettlementEntity $settlement,
        string $destinationMerchantId,
        string $balanceType): Entity
    {
        assert($settlement->hasTransaction(), true); // nosemgrep : razorpay:assert-fix-false-positives

        $this->trace->info(TraceCode::SETTLEMENT_TRANSFER_CREATE_INITIATED,
            [
                'balance_type'            => $balanceType,
                'settlement_id'           => $settlement->getId(),
                'destination_merchant_id' => $destinationMerchantId,
            ]);

        $destinationBalance = $this->repo
                                   ->balance
                                   ->getMerchantBalanceByType($destinationMerchantId, $balanceType);

        //
        // We should not transfer the money to the same balance ID
        //
        if ($destinationBalance->getId() === $settlement->getBalanceId())
        {
            throw new BadRequestValidationFailureException(
                'source and destination balance id should not be same');
        }

        $txnCore = (new Transaction\Core);

        $settlementTransfer = $this->transaction(function () use ($settlement, $destinationBalance, $txnCore)
        {
            $settlementTransfer = $this->buildSettlementTransferEntity(
                $settlement,
                $destinationBalance);

            //
            // marking settlement as processed as its internally transferred to parent merchant
            //
            $settlement->setStatus(Status::PROCESSED);

            $transaction = $txnCore->createFromSettlementTransfer($settlementTransfer);

            $this->repo->saveOrFail($settlementTransfer);

            $this->repo->saveOrFail($transaction);

            $this->repo->saveOrFail($settlement);

            (new Destination\Core)->register($settlement, $settlementTransfer);

            $this->trace->info(TraceCode::SETTLEMENT_TRANSFER_CREATED,
                [
                    'destination_balance_id'            => $destinationBalance->getId(),
                    'settlement_id'                     => $settlement->getId(),
                    'transaction_id'                    => $transaction->getId(),
                    'settlement_transfer_id'            => $settlementTransfer->getId(),
                ]);

            return $settlementTransfer;
        });

        return $settlementTransfer;
    }

    /**
     * creates entity based on given parameter and
     * validate the attribute against create rules defined
     *
     * @param SettlementEntity $settlement
     * @param Balance\Entity $destinationBalance
     *
     * @return Entity
     */
    protected function buildSettlementTransferEntity(
        SettlementEntity $settlement,
        Balance\Entity $destinationBalance)
    {
        $entityData = [
            Entity::CURRENCY                  => Currency::INR,
            Entity::AMOUNT                    => $settlement->getAmount(),
            Entity::FEE                       => $settlement->getFees(),
            Entity::TAX                       => $settlement->getTax(),
            Entity::BALANCE_ID                => $destinationBalance->getId(),
            Entity::MERCHANT_ID               => $destinationBalance->getMerchantId(),
            Entity::SETTLEMENT_ID             => $settlement->getId(),
            Entity::SOURCE_MERCHANT_ID        => $settlement->getMerchantId(),
            Entity::SETTLEMENT_TRANSACTION_ID => $settlement->getTransactionId(),
        ];

        $entity = (new Entity)->generateId();

        $entity->build($entityData);

        return $entity;
    }
}
