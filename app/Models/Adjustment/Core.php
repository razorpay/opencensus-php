<?php

namespace RZP\Models\Adjustment;

use DB;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Dispute;
use RZP\Models\Payment;
use RZP\Constants\Table;
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

    public function splitAdjustments($file): array
    {
        $fileContents = $this->parseExcelFile($file);

        $this->trace->info(
            TraceCode::ADJUSTMENT_SPLIT_REQUEST,
            [
                'total_adjustments' => count($fileContents)
            ]);

        $failed = 0;
        $failedIds = [];
        $succeeded = 0;
        $processed = 0;

        foreach ($fileContents as $adjustment)
        {
            $adjId = $adjustment[Entity::ID];

            $this->trace->info(
                TraceCode::ADJUSTMENT_SPLIT_REQUEST,
                [
                    'id'          => $adjId,
                    'payment_ids' => $adjustment[Dispute\Entity::PAYMENT_ID]
                ]);

            $paymentIds = explode(',', trim($adjustment[Dispute\Entity::PAYMENT_ID]));

            $data = [];

            $amount = 0;

            $count = 1;

            if (($adjustment[Entity::AMOUNT] >= 0) === true)
            {
                $this->trace->info(
                    TraceCode::ADJUSTMENT_SPLIT_ERROR,
                    [
                        'id'     => $adjId,
                        'reason' => 'Positive amount not expected'
                    ]);

                $failed++;

                $failedIds[] = $adjId;

                continue;
            }

            foreach ($paymentIds as $paymentId)
            {
                $paymentId = trim($paymentId);

                $payment = $this->repo->payment->findOrFail($paymentId);

                $amount += $payment[Payment\Entity::AMOUNT];

                $data[$paymentId] = $payment[Payment\Entity::AMOUNT];
            }

            if ($amount !== (int) abs($adjustment[Entity::AMOUNT]))
            {

                $failed++;

                $failedIds[] = $adjId;

                $this->trace->debug(
                    TraceCode::ADJUSTMENT_SPLIT_ERROR,
                    [
                        'id' => $adjId,
                    ]);
            }
            else
            {
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

                        $setlDetails->update([Settlement\Details\Entity::COUNT => count($data)]);

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

                                $newAdj = $this->insertSplitAdjustment($newAdjId, $amount, $adjustment);

                                $adjId = $newAdjId;

                                $newTxnId = $this->getNextId($txnId);

                                $newTxn = $this->insertSplitTransaction($newTxnId, $newAdjId, $amount, $adjustment, $balance, $txn);

                                $txnId = $newTxnId;

                                $balance -= $amount;

                                $newAdj->transaction()->associate($newTxn);

                                $this->repo->adjustment->saveOrFail($newAdj);
                            }
                        }
                    });

                    $succeeded++;
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

                    $failed++;

                    $failedIds[] = $adjustment[Entity::ID];
                }
            }

            $processed++;
        }

        return [
            'success' => $succeeded,
            'failure' => $failed,
            'total'   => $processed,
            'failed'  => $failedIds
        ];
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

    protected function insertSplitAdjustment(string $newAdjId, int $amount, array $adjustment): Entity
    {
        DB::table(Table::ADJUSTMENT)->insert(
            [
                Entity::ID          => $newAdjId,
                Entity::MERCHANT_ID => $adjustment[Entity::MERCHANT_ID],
                Entity::AMOUNT      => 0 - $amount,
                Entity::CURRENCY    => 'INR',
                Entity::CHANNEL     => 'kotak',
                Entity::DESCRIPTION => $adjustment[Entity::DESCRIPTION],
                Entity::CREATED_AT  => $adjustment[Entity::CREATED_AT],
                Entity::UPDATED_AT  => $adjustment[Entity::UPDATED_AT],
            ]
        );

        return $this->repo->adjustment->findOrFail($newAdjId);
    }

    protected function insertSplitTransaction(
        string $newTxnId, string $newAdjId,
        int $amount,
        array $adjustment,
        int $balance,
        Transaction\Entity $txn): Transaction\Entity
    {
        DB::table(Table::TRANSACTION)->insert(
            [
                Transaction\Entity::ID            => $newTxnId,
                Transaction\Entity::ENTITY_ID     => $newAdjId,
                Transaction\Entity::TYPE          => Constants\Entity::ADJUSTMENT,
                Transaction\Entity::MERCHANT_ID   => $adjustment[Entity::MERCHANT_ID],
                Transaction\Entity::AMOUNT        => abs($amount),
                Transaction\Entity::FEE           => 0,
                Transaction\Entity::SERVICE_TAX   => 0,
                Transaction\Entity::TAX           => 0,
                Transaction\Entity::DEBIT         => abs($amount),
                Transaction\Entity::CREDIT        => 0,
                Transaction\Entity::CURRENCY      => 'INR',
                Transaction\Entity::BALANCE       => $balance - $amount,
                Transaction\Entity::GATEWAY_FEE   => 0,
                Transaction\Entity::API_FEE       => 0,
                Transaction\Entity::GRATIS        => 0,
                Transaction\Entity::FEE_CREDITS   => 0,
                Transaction\Entity::CHANNEL       => 'kotak',
                Transaction\Entity::FEE_BEARER    => -1,
                Transaction\Entity::FEE_MODEL     => -1,
                Transaction\Entity::CREDIT_TYPE   => 'default',
                Transaction\Entity::ON_HOLD       => 0,
                Transaction\Entity::SETTLED       => 1,
                Transaction\Entity::SETTLED_AT    => $txn->getSettledAt(),
                Transaction\Entity::SETTLEMENT_ID => $txn->getSettlementId(),
                Transaction\Entity::RECONCILED_AT => $txn->getReconciledAt(),
                Transaction\Entity::CREATED_AT    => $txn->getCreatedAt(),
                Transaction\Entity::UPDATED_AT    => $txn->getUpdatedAt()
            ]
        );

        return $this->repo->transaction->findOrFail($newTxnId);
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
