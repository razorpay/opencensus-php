<?php

namespace RZP\Models\Dispute;

use DB;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\Admin\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

class Core extends Base\Core
{
    use FileHandlerTrait;
  
    const DEBIT_ADJUSTMENT_DESCRIPTION = 'Debit disputed amount';
    const CREDIT_ADJUSTMENT_DESCRIPTION = 'Credit to reverse a previous dispute debit';

    /**
     * @param Payment\Entity $payment
     * @param Reason\Entity  $reason
     * @param array          $input
     *
     * @return Entity
     */
    public function create(
        Payment\Entity $payment,
        Reason\Entity $reason,
        array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_CREATE_REQUEST,
            [
                'input'      => $input,
                'payment_id' => $payment->getId()
            ]);

        $validator = new Validator();

        $validator->validatePaymentForDispute($input, $payment);

        $validator->validateDeductOnsetForNonTransactionalPhase($input);

        $parent = $this->checkAndGetParent($input);

        $dispute = (new Entity)->build($input);

        $this->setRelationsAndDerivedAttributes($dispute, $parent, $payment, $reason);

        // entity id is required to create associated transaction
        $dispute->generateId();

        $this->app['workflow']
            ->setEntityAndId($dispute->getEntity(), $dispute->getId())
            ->handle((new \stdClass), $dispute);

        $dispute->setAuditAction(Action::CREATE_DISPUTE);

        $payment->setDisputed(true);

        $dispute = $this->repo->transaction(function() use ($dispute)
        {
            if ($dispute->getDeductAtOnset() === true)
            {
                $this->createNegativeAdjustmentAndUpdateDispute($dispute);
            }

            $this->repo->saveOrFail($dispute->payment);

            $this->repo->saveOrFail($dispute);

            return $dispute;
        });

        // TODO: Send email to merchant

        return $dispute;
    }

    /**
     * @param Entity $dispute
     * @param array  $input
     *
     * @return Entity
     */
    public function update(Entity $dispute, array $input): Entity
    {
        $this->trace->info(
            TraceCode::DISPUTE_EDIT_REQUEST,
            array_merge($input, [Entity::ID => $dispute->getId()])
        );

        $parent = $this->checkAndGetParent($input, $dispute);
        
        $dispute->edit($input);

        $dispute->setAuditAction(Action::EDIT_DISPUTE);

        if ($parent !== null)
        {
            $dispute->parent()->associate($parent);
        }

        return $this->repo->transaction(function() use ($dispute, $input)
        {
            $this->handleDisputeClosure($dispute, $input);

            $this->repo->saveOrFail($dispute);

            return $dispute;
        });
    }

    /**
     * @param $file
     *
     * @return array
     */
    public function migrateOldAdjustments($file): array
    {
        $fileContents = $this->parseExcelFile($file);

        $this->trace->info(
            TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_REQUEST,
            [
                'total_adjustments' => count($fileContents)
            ]);

        $failed = 0;
        $failedIds = [];
        $succeeded = 0;
        $processed = 0;

        foreach ($fileContents as $adjustment)
        {
            $this->trace->info(
                TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_REQUEST,
                [
                    'id'         => $adjustment[Adjustment\Entity::ID],
                    'payment_id' => $adjustment[Entity::PAYMENT_ID]
                ]);

            $id = (new Entity)->generateUniqueId();

            try
            {
                DB::transaction(function() use ($id, $adjustment) {
                    DB::table(Table::DISPUTE)->insert(
                        [
                            Entity::ID                 => $id,
                            Entity::MERCHANT_ID        => $adjustment[Adjustment\Entity::MERCHANT_ID],
                            Entity::PAYMENT_ID         => $adjustment[Entity::PAYMENT_ID],
                            Entity::REASON_ID          => 'NotAvailable00',
                            Entity::AMOUNT             => abs($adjustment[Adjustment\Entity::AMOUNT]),
                            Entity::CURRENCY           => $adjustment[Adjustment\Entity::CURRENCY],
                            Entity::REASON_CODE        => 'not_available',
                            Entity::REASON_DESCRIPTION => 'Not Available',
                            Entity::PHASE              => $adjustment[Entity::PHASE],
                            Entity::STATUS             => Status::LOST,
                            Entity::DEDUCT_AT_ONSET    => 0,
                            Entity::CREATED_AT         => $adjustment[Adjustment\Entity::CREATED_AT],
                            Entity::UPDATED_AT         => $adjustment[Adjustment\Entity::UPDATED_AT],
                            Entity::RAISED_ON          => $adjustment[Adjustment\Entity::CREATED_AT],
                            Entity::EXPIRES_ON         => $adjustment[Adjustment\Entity::UPDATED_AT],
                        ]
                    );

                    DB::table(Table::ADJUSTMENT)
                        ->where(Adjustment\Entity::ID, $adjustment[Adjustment\Entity::ID])
                        ->update(
                            [
                                Adjustment\Entity::ENTITY_TYPE => \RZP\Constants\Entity::DISPUTE,
                                Adjustment\Entity::ENTITY_ID   => $id,
                            ]
                        );
                });

                $succeeded++;
            }
            catch (\Exception $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::DISPUTE_ADJUSTMENT_MIGRATE_ERROR,
                    [
                        'id'         => $adjustment[Adjustment\Entity::ID],
                        'payment_id' => $adjustment[Entity::PAYMENT_ID]
                    ]);

                $failed++;

                $failedIds[] = $adjustment[Adjustment\Entity::ID];
            }

            $processed++;
        }

        return [
            'success' => $succeeded,
            'failure' => $failed,
            'total' => $processed,
            'failed' => $failedIds
        ];
    }

    protected function setRelationsAndDerivedAttributes(
        Entity $dispute,
        Entity $parent = null,
        Payment\Entity $payment,
        Reason\Entity $reason)
    {
        $merchant = $payment->merchant;

        $dispute->setCurrency($payment->getCurrency());

        $dispute->setReasonCode($reason->getCode());

        $dispute->setReasonDescription($reason->getDescription());

        $dispute->payment()->associate($payment);

        $dispute->merchant()->associate($merchant);

        $dispute->reason()->associate($reason);

        $dispute->parent()->associate($parent);
    }

    protected function handleDisputeClosure(Entity $dispute, array $input)
    {
        if ($dispute->isClosed() === false)
        {
            return;
        }

        $dispute->setResolvedAt(Carbon::now()->getTimestamp());

        $payment = $dispute->payment;

        $payment->setDisputed(false);

        $this->repo->saveOrFail($payment);

        if ($dispute->isLost() === true)
        {
            $this->handleLostDisputeAdjustments($dispute, $input);
        }

        if ($this->shouldReverse($dispute) === true)
        {
            $this->createPositiveAdjustmentAndUpdateDispute($dispute);
        }
    }

    protected function handleLostDisputeAdjustments(Entity $dispute, array $input)
    {
        $acceptedDisputeAmount = $this->getAcceptedDisputeAmount($dispute, $input);

        if ($dispute->getAmountDeducted() === 0)
        {
            $this->createNegativeAdjustmentAndUpdateDispute($dispute, $acceptedDisputeAmount);
        }
        else
        {
            // If amount_deducted is not zero, it is equal to the disputed amount only

            if (($dispute->getAmountDeducted() - $acceptedDisputeAmount) > 0)
            {
                $this->createPositiveAdjustmentAndUpdateDispute($dispute,
                    $dispute->getAmountDeducted() - $acceptedDisputeAmount);
            }
        }
    }

    protected function shouldReverse(Entity $dispute): bool
    {
        return (($dispute->isWon() === true) and
                ($dispute->getAmountDeducted() > 0) and
                ($dispute->getAmountReversed() === 0));
    }

    protected function createNegativeAdjustmentAndUpdateDispute(Entity $dispute, int $amount = 0)
    {
        if ($amount === 0)
        {
            $amount = $dispute->getAmount();
        }

        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => 0 - $amount,
            Adjustment\Entity::DESCRIPTION => self::DEBIT_ADJUSTMENT_DESCRIPTION,
        ];

        (new Adjustment\Core)->createDisputeAdjustment($input, $dispute);

        $dispute->setAmountDeducted($amount);
    }

    protected function createPositiveAdjustmentAndUpdateDispute(Entity $dispute, int $amount = 0)
    {
        if ($amount === 0)
        {
            $amount = $dispute->getAmountDeducted();
        }

        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => $amount,
            Adjustment\Entity::DESCRIPTION => self::CREDIT_ADJUSTMENT_DESCRIPTION,
        ];

        (new Adjustment\Core)->createDisputeAdjustment($input, $dispute);

        $dispute->setAmountReversed($amount);
    }

    protected function getAcceptedDisputeAmount(Entity $dispute, array $input)
    {
        if (isset($input[Entity::ACCEPTED_AMOUNT]) === false)
        {
            return $dispute->getAmount();
        }

        $dispute->getValidator()->validateAcceptedDisputeAmount($dispute->getAmount(), $input);

        return $input[Entity::ACCEPTED_AMOUNT];
    }

    /**
     *  Checks if the new parent is not same as existing parent
     *  and is eligible to become a parent (has no child)
     *
     * @param array $input
     * @param Entity|null $dispute
     * @return null
     */
    protected function checkAndGetParent(array $input, Entity $dispute = null)
    {
        if (isset($input[Entity::PARENT_ID]) === false)
        {
            return null;
        }

        if ($dispute !== null)
        {
            // Check if new parent is existing parent

            if (($dispute->isChildDispute() === true) and
                ($dispute->getParentId() === $input[Entity::PARENT_ID]))
            {
                $this->trace->info(
                    TraceCode::DISPUTE_SAME_PARENT_LINKING,
                    [
                        'input'      => $input,
                        'dispute_id' => $dispute->getId()
                    ]);

                unset($input[Entity::PARENT_ID]);

                return null;
            }
        }

        $parent = $this->repo->dispute->findOrFailPublic($input[Entity::PARENT_ID]);

        $parent->getValidator()->validateDisputeCanBecomeParent();

        return $parent;
    }
}
