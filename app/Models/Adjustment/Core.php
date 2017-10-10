<?php

namespace RZP\Models\Adjustment;

use DB;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice as MerchantInvoice;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;

    public function createAdjustment(array $input, $merchant): Entity
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        $adj = (new Adjustment\Entity)->build($input);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        return $this->transaction([$this, 'createAdjInTransaction'], $adj, $merchant);
    }

    public function createFeesAdjustment(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::FEE_ADJUSTMENT_CREATE_REQUEST,
            [
                'input' => $input,
                'merchant' => $merchant->getId()
            ]);

        (new Validator)->validateInput('fee_adjustment', $input);

        // Create input for adjustment
        $adjInput = $input;

        $amount = $adjInput[Entity::AMOUNT] ?? 0;

        $tax =  $adjInput[MerchantInvoice\Entity::TAX] ?? 0;

        $adjInput[Entity::AMOUNT] = $amount + $tax;

        $adj = (new Adjustment\Entity)->build($adjInput);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        // 1. Create adjustment
        // 2. Create Invoice entity for adjustment
        $adjustment = $this->repo->transaction(function() use ($adj, $merchant, $input)
        {
            $adjustment = $this->createAdjInTransaction($adj, $merchant);

            (new Merchant\Invoice\Core)->createAdjustmentInvoiceEntity($adj, $input);

            return $adjustment;
        });

        return $adjustment;
    }

    public function createDisputeAdjustment(array $input, Dispute\Entity $dispute): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_ADJUSTMENT_CREATE_REQUEST,
            [
                'input'       => $input,
                'merchant_id' => $dispute->getMerchantId()
            ]);

        $adjustment = $this->createAdjustment($input, $dispute->merchant);

        $adjustment->entity()->associate($dispute);

        $this->repo->saveOrFail($adjustment);

        return $adjustment;
    }

    public function splitAdjustments(array $input): array
    {
        $this->trace->info(
            TraceCode::ADJUSTMENT_SPLIT_REQUEST,
            [
                'input' => $input
            ]);

        (new Validator)->validateInput('split_adjustment', $input);

        $adjustment = $input;

        $count = 1;

        list($valid, $data) = $this->verifyAmountsToSplit($adjustment);

        if ($valid === false)
        {
            throw new Exception\BadRequestValidationFailureException('Amounts do not seem to add up');
        }
        try
        {
            $this->repo->transaction(function () use ($data, $count, $adjustment)
            {

                $originalAmount = $adjustment[Entity::AMOUNT];

                $txn = $this->repo->transaction->findOrFail($adjustment[Entity::TRANSACTION_ID]);

                $setlDetails = $this->repo->settlement_details->fetch([
                    Settlement\Details\Entity::SETTLEMENT_ID => $txn->getSettlementId(),
                    Settlement\Details\Entity::COMPONENT     => $txn->getType()
                ])->first();

                $setlDetails->update([Settlement\Details\Entity::COUNT => count($data) + $setlDetails->getCount() - 1]);

                $balance = 0;

                $adjId = $adjustment[Entity::ID];

                $txnId = $txn->getId();

                foreach ($data as $id => $amount)
                {
                    if ($count === 1)
                    {
                        $adj = $this->repo->adjustment->findOrFail($adjId);

                        $adj->update([Entity::AMOUNT => 0 - $amount]);

                        $this->updateTransactionAmountAndBalance($txn, $amount, $originalAmount);

                        $balance = $txn->getBalance();

                        $count++;
                    }
                    else
                    {
                        $newAdjId = $this->getNextId($adjId);

                        $newAdj = $this->insertSplitAdjustment($newAdjId, $amount, $adjId);

                        $adjId = $newAdjId;

                        $newTxnId = $this->getNextId($txnId);

                        $newTxn = $this->insertSplitTransaction($newTxnId, $newAdj, $amount, $balance, $txn);

                        $txnId = $newTxnId;

                        $balance -= $amount;

                        $newAdj->transaction()->associate($newTxn);

                        $this->repo->adjustment->saveOrFail($newAdj);
                    }
                }
            });
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'          => $adjustment[Entity::ID],
                    'payment_ids' => $adjustment[Dispute\Entity::PAYMENT_ID]
                ]);
        }

        return ['success' => true];
    }

    protected function verifyAmountsToSplit(array $adjustment): array
    {
        $paymentIds = explode(',', trim($adjustment[Dispute\Entity::PAYMENT_ID]));

        $data = [];

        $amount = 0;

        $adjId = $adjustment[Entity::ID];

        if (($adjustment[Entity::AMOUNT] >= 0) === true)
        {
            $this->trace->info(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id'     => $adjId,
                    'reason' => 'Positive amount not expected'
                ]);

            return [false, []];
        }

        foreach ($paymentIds as $paymentId)
        {
            $paymentId = trim($paymentId);

            $payment = $this->repo->payment->findByPublicId($paymentId);

            $amount += $payment[Payment\Entity::AMOUNT];

            $data[$paymentId] = $payment[Payment\Entity::AMOUNT];
        }

        if ($amount !== (int) abs($adjustment[Entity::AMOUNT]))
        {
            $this->trace->debug(
                TraceCode::ADJUSTMENT_SPLIT_ERROR,
                [
                    'id' => $adjId,
                ]);

            return [false,[]];
        }

        return [true, $data];
    }

    protected function createAdjInTransaction($adj, $merchant): Entity
    {
        $this->repo->assertTransactionActive();

        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($adj);

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_SUCCESS,
            $adj->toArrayPublic());

        return $adj;
    }

    /**
     * @param Transaction\Entity $txn
     * @param int                $amount
     * @param int                $originalAmount
     *
     * DO NOT USE THIS FUNCTION CASUALLY, THIS IS
     * WRITTEN FOR THE ABOVE SPLIT MIGRATION BUT SHOULD
     * NOT BE NEEDED IN GENERAL.
     */
    protected function updateTransactionAmountAndBalance(
        Transaction\Entity $txn,
        int $amount,
        int $originalAmount)
    {
        $newBalance = $txn->getBalance() + abs($originalAmount) - $amount;

        $txn->update([
            Transaction\Entity::AMOUNT  => abs($amount),
            Transaction\Entity::DEBIT   => abs($amount),
            Transaction\Entity::BALANCE => $newBalance
        ]);

        $this->repo->saveOrFail($txn);
    }

    protected function insertSplitAdjustment(string $newAdjId, int $amount, string $adjId): Entity
    {
        $adj = $this->repo->adjustment->findOrFail($adjId);

        $newAdj = $adj->replicate();

        $newAdj->setAmount(0 - $amount);

        $newAdj->setCreatedAt($adj->getCreatedAt());

        $newAdj->setUpdatedAt($adj->getUpdatedAt());

        $newAdj->setId($newAdjId);

        $newAdj->save();

        return $newAdj;
    }

    protected function insertSplitTransaction(
        string $newTxnId,
        Entity $newAdj,
        int $amount,
        int $balance,
        Transaction\Entity $txn): Transaction\Entity
    {
        $newTxn = $txn->replicate();

        $newTxn->setCreatedAt($txn->getCreatedAt());

        $newTxn->setUpdatedAt($txn->getUpdatedAt());

        $newTxn->setAmount(abs($amount));

        $newTxn->setDebit(abs($amount));

        $newTxn->setBalance($balance - $amount);

        $newTxn->setId($newTxnId);

        $newTxn->source()->associate($newAdj);

        $newTxn->save();

        return $newTxn;
    }

    /**
     * @param  string $oldId
     *
     * @return string $newId
     *
     * This function does not cover a lot of corner cases
     * as the current data does not need them. Those checks
     * should be added if ever needed.
     */
    protected function getNextId(string $oldId)
    {
        $last = substr($oldId, -1, 1);

        $newId = substr_replace($oldId, ++$last, -1, 1);

        return $newId;
    }
}
