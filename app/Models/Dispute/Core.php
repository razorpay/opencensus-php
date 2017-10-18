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

    protected $disputeParent;

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

        (new Validator)->validatePaymentForDispute($input, $payment);

        $this->checkAndGetParentDispute($input);

        $dispute = (new Entity)->build($input);

        $this->setRelationsAndDerivedAttributes($dispute, $payment, $reason);

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

        $this->checkAndGetParentDispute($input, $dispute);

        $dispute->edit($input);

        $dispute->setAuditAction(Action::EDIT_DISPUTE);

        if ($this->disputeParent !== null)
            $dispute->parent()->associate($this->disputeParent);

        return $this->repo->transaction(function() use ($dispute)
        {
            $this->handleDisputeClosure($dispute);

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

        if ($this->disputeParent !== null)
            $dispute->parent()->associate($this->disputeParent);
    }

    protected function handleDisputeClosure(Entity $dispute)
    {
        if ($dispute->isClosed() === false)
        {
            return;
        }

        $dispute->setResolvedAt(Carbon::now()->getTimestamp());

        $payment = $dispute->payment;

        $payment->setDisputed(false);

        $this->repo->saveOrFail($payment);

        if (($dispute->isLost() === true) and
            ($dispute->getAmountDeducted() === 0))
        {
            $this->createNegativeAdjustmentAndUpdateDispute($dispute);
        }

        if ($this->shouldReverse($dispute) === true)
        {
            $this->createPositiveAdjustmentAndUpdateDispute($dispute);
        }
    }

    protected function createPositiveAdjustmentAndUpdateDispute(Entity $dispute)
    {
        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => $dispute->getAmountDeducted(),
            Adjustment\Entity::DESCRIPTION => self::CREDIT_ADJUSTMENT_DESCRIPTION,
        ];

        (new Adjustment\Core)->createDisputeAdjustment($input, $dispute);

        $dispute->setAmountReversed($dispute->getAmountDeducted());
    }

    protected function shouldReverse(Entity $dispute): bool
    {
        return (($dispute->isWon() === true) and
                ($dispute->getAmountDeducted() > 0) and
                ($dispute->getAmountReversed() === 0));
    }

    protected function createNegativeAdjustmentAndUpdateDispute(Entity $dispute)
    {
        $dispute->setAmountDeducted($dispute->getAmount());

        $input = [
            Adjustment\Entity::CURRENCY    => $dispute->getCurrency(),
            Adjustment\Entity::AMOUNT      => 0 - $dispute->getAmount(),
            Adjustment\Entity::DESCRIPTION => self::DEBIT_ADJUSTMENT_DESCRIPTION,
        ];

        (new Adjustment\Core)->createDisputeAdjustment($input, $dispute);
    }

    /**
     *  Checks if the new parent, if exists, is not same as the old parent
     *  and is not the parent of any other dispute entity
     */
    protected function checkAndGetParentDispute(array $input, Entity $dispute = null)
    {
        if(isset($input[Entity::PARENT_ID]) === false)
            return;

        $validator = new Validator($dispute);

        if($dispute !== null)
        {
            $validator->validateParentDisputeWithExistingParent($input);
        }

        $disputeParent = $this->repo->dispute->findOrFailPublic($input[Entity::PARENT_ID]);

        $validator->validateParentDispute($disputeParent);

        $this->disputeParent = $disputeParent;
    }
}
