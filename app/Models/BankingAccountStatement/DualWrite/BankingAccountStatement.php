<?php

namespace RZP\Models\BankingAccountStatement\DualWrite;

use App;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Models\Payout\DualWrite\Reversal;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\External\Core as ExternalCore;
use RZP\Models\Payout\Entity as PayoutEntity;
use RZP\Models\BankingAccountStatement\Entity;
use RZP\Models\Transaction\Entity as TxnEntity;

class BankingAccountStatement extends Base
{
    const PAYOUT = 'payout';
    const PAYOUT_REVERSAL = 'payout_reversal';
    const EXTERNAL = 'external';
    protected $columnsToUnset = [
        Entity::GATEWAY_REF_NUMBER,
        Entity::BAS_DETAILS_ID,
    ];
    public function dualWritePSBas(string $id)
    {
        $this->trace->info(
            TraceCode::BAS_DUAL_WRITE_INIT,
            ['bas_id' => $id]
        );

        $bas = $this->getAPIBASFromPayoutService($id);

        if (empty($bas) === true)
        {
            return null;
        }

        /** @var Entity $apiBAS */
        $apiBAS = $this->repo->banking_account_statement->find($id);

        if (empty($apiBAS) === false)
        {
            $bas = $apiBAS->setRawAttributes($bas->getAttributes());
        }

        $this->updateLinking($bas);

        // This is needed otherwise it check whether the associated entities are created or not.
        $bas->setIgnoreRelationsForServiceEntities();

        $this->repo->banking_account_statement->saveOrFail($bas);

        $this->trace->info(
            TraceCode::BAS_DUAL_WRITE_COMPLETED,
            ['bas_id' => $id]
        );

        return $bas;
    }

    public function getAPIBASFromPayoutService(string $id, bool $sync = false)
    {
        $payoutServiceBASs = $this->repo->banking_account_statement->getPayoutServiceBAS($id);

        if (count($payoutServiceBASs) === 0)
        {
            $this->trace->error(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_BAS_NOT_FOUND,
                [Entity::BAS_ID => $id]
            );

            throw new BadRequestException(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_BAS_NOT_FOUND,
                Entity::BAS_ID,
                [Entity::BAS_ID => $id]
            );
        }

        $psBAS = $payoutServiceBASs[0];

        // converts the stdClass object into associative array.
        $this->attributes = get_object_vars($psBAS);

        $this->processModifications();

        $bas = new Entity;

        $bas->setRawAttributes($this->attributes, $sync);

        // Explicitly setting the connection.
        $bas->setConnection($this->mode);

        // This will ensure that updated_at columns are not overridden by saveOrFail.
        $bas->timestamps = false;

        return $bas;
    }

    public function updateLinking(Entity $bas)
    {
        $bas->setTransactionId($bas->getId());

        $txn = $this->getTransaction($bas);

        switch ($bas->getEntityType())
        {
            case self::PAYOUT:
                $this->updateBasAndTxnWithPayoutData($bas, $txn);
                break;

            case self::PAYOUT_REVERSAL:
                $this->updateBasAndTxnWithReversalData($bas, $txn);
                break;

            case self::EXTERNAL:
                $this->updateBasAndTxnWithExternalData($bas, $txn);
                break;
        }
    }

    public function getTransaction(Entity $bas)
    {
        if (!empty($bas->transaction))
        {
            $this->trace->info(
                TraceCode::BAS_DUAL_WRITE_USING_EXISTING_TRANSACTION,
                [
                    Entity::BAS_ID => $bas->getId(),
                    Entity::TRANSACTION_ID => $bas->transaction->getId(),
                ]
            );

            return $bas->transaction;
        }

        $balance = $this->repo->balance->getBalanceByMerchantIdAccountNumberAndChannelOrFail($bas->getMerchantId(),
            $bas->getAccountNumber(),
            $bas->getChannel());

        $txn = new TxnEntity;
        $txn->setId($bas->getId());

        $currentTimestamp = Carbon::now(Timezone::IST)->getTimestamp();

        $attributes = [
            "id"                  => $bas->getId(),
            "merchant_id"         => $bas->getMerchantId(),
            "amount"              => $bas->getAmount(),
            "fee"                 => 0,
            "mdr"                 => 0,
            "tax"                 => 0,
            "pricing_rule_id"     => null,
            "debit"               => $bas->isTypeDebit() ?: 0,
            "credit"              => $bas->isTypeCredit() ?: 0,
            "currency"            => $bas->getCurrency(),
            "balance"             => $bas->getBalance(),
            "gateway_fee"         => 0,
            "gateway_service_tax" => 0,
            "api_fee"             => 0,
            "fee_credits"         => 0,
            "escrow_balance"      => 0,
            "channel"             => $bas->getChannel(),
            "settled_at"          => $currentTimestamp,
            "reconciled_at"       => $currentTimestamp,
            "reconciled_type"     => 'na',
            "balance_id"          => $balance->getId(),
            "posted_at"           => $bas->getPostedDate(),
            "created_at"          => $bas->getCreatedAt(),
            "updated_at"          => $currentTimestamp
        ];

        $txn->setRawAttributes($attributes);

        return $txn;
    }

    public function updateBasAndTxnWithPayoutData(Entity $bas, TxnEntity $txn)
    {
        $entityId = $bas->getEntityId();

        /** @var PayoutEntity $payout */
        $payout = $this->repo->payout->find($entityId);

        if (empty($payout) === true)
        {
            /** @var PayoutEntity $payout */
            $payout = (new PayoutCore)->getAPIModelPayoutFromPayoutService($entityId);
        }

        if (empty($payout))
        {
            throw new LogicException("Payout entity don't exist for bas. entity_id: $entityId");
        }

        if ($txn->getType() == self::EXTERNAL)
        {
            (new ExternalCore)->delete($txn->source);
        }

        $txn->source()->associate($payout);
        $txn->setFee($payout->getFee());
        $txn->setTax($payout->getTax());

        $this->repo->transaction->saveOrFail($txn);

        $bas->setEntityType(EntityConstants::PAYOUT);
        $bas->source()->associate($payout);
        $bas->transaction()->associate($txn);
    }

    public function updateBasAndTxnWithReversalData(Entity $bas, TxnEntity $txn)
    {
        $isConvertedFromExternal = false;

        if ($txn->getType() == self::EXTERNAL)
        {
            $isConvertedFromExternal = true;
        }

        $payoutId = $bas->getEntityId();

        /** @var \RZP\Models\Reversal\Entity $reversal */
        $reversal = $this->getReversalFromPayoutId($payoutId, $bas, $isConvertedFromExternal);

        if (empty($reversal))
        {
            throw new LogicException("Reversal entity don't exist for bas. entity_id: $payoutId");
        }

        if ($txn->getType() == self::EXTERNAL)
        {
            (new ExternalCore)->delete($txn->source);
        }

        $txn->source()->associate($reversal);
        $txn->setFee(0);
        $txn->setTax(0);

        $this->repo->transaction->saveOrFail($txn);

        $bas->setEntityType(EntityConstants::REVERSAL);
        $bas->source()->associate($reversal);
        $bas->transaction()->associate($txn);
    }

    public function updateBasAndTxnWithExternalData(Entity $bas, TxnEntity $txn)
    {
        $bas->setEntityId($bas->getId());
        $external = $bas->source;

        if (empty($external))
        {
            $external = (new ExternalCore)->createExternalEntity($bas);
            $external->setId($bas->getEntityId());
        }

        $this->repo->external->saveOrFail($external);

        $txn->source()->associate($external);
        $txn->setFee(0);
        $txn->setTax(0);

        $this->repo->transaction->saveOrFail($txn);

        $bas->setEntityType(EntityConstants::EXTERNAL);
        $bas->source()->associate($external);
        $bas->transaction()->associate($txn);
    }

    public function getReversalFromPayoutId(string $payoutId, Entity $bas, bool $isConvertedFromExternal)
    {
        $reversal = $this->repo->reversal->findReversalForPayout($payoutId);

        if (empty($reversal) === true)
        {
            $reversal = (new Reversal)->getAPIReversalFromPayoutService($payoutId);
        }

        if (empty($reversal))
        {
            $input = [
                Entity::BAS_ID                  => $bas->getId(),
                Entity::ENTITY_ID               => $bas->getEntityId(),
                Entity::ENTITY_TYPE             => $bas->getEntityType(),
                Entity::MERCHANT_ID             => $bas->getMerchantId(),
                Entity::TRANSACTION_DATE        => $bas->getTransactionDate(),
                Entity::CONVERTED_FROM_EXTERNAL => $isConvertedFromExternal,
            ];

            try
            {
                (new PayoutCore)->payoutUpdateByBASRecon($input);
            }
            catch(\Throwable $exception) {}
        }
        else {
            return $reversal;
        }

        $reversal = $this->repo->reversal->findReversalForPayout($payoutId);

        if (empty($reversal) === true)
        {
            $reversal = (new Reversal)->getAPIReversalFromPayoutService($payoutId);
        }

        return $reversal;
    }
}
