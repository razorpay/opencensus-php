<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use RZP\Exception;
use RZP\Constants\Entity;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Base\Reconciliation\EntityProcessor as BaseEntityProcessor;

class EntityProcessor extends BaseEntityProcessor
{
    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    protected function updateAttemptEntity()
    {
        $failureReason = null;

        $status = Attempt\Status::FAILED;

        $bankStatusCode = $this->fta->getBankStatusCode();

        if ($bankStatusCode === Status::PROCESSED)
        {
            $remarks = $this->fta->getRemarks();

            if ((empty($remarks) === false) and
                (in_array($remarks, self::SUCCESS_STATUS) === false))
            {
                $status = Attempt\Status::FAILED;

                $failureReason = 'Reconciliation';
            }
            else
            {
                $status = Attempt\Status::PROCESSED;
            }
        }

        // Verify status
        $oldStatus = $this->fta->getStatus();

        if ($oldStatus === $status)
        {
            return;
        }

        //
        // If the old and new status do not match
        //

        if ($this->fta->isPendingReconciliation() === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Old and new status not matching. ' .
                'Old status: ' . $oldStatus . ' New status: ' . $status .
                'Entity Id: ' . $this->fta->getPublicId());
        }

        $this->fta->setFailureReason($failureReason);

        $this->fta->setStatus($status);

        //
        // Set the fire webhook flag as true only for the settlements that
        // have to be updated in the current settlement cycle. For the settlements
        // that have been settled in the previous cycles, this flag stays false.
        //
        $this->fireWebhook = true;

        if ($this->isMerchantLevelError() === true)
        {
            $this->sendFailureEmailToMerchant = true;

            // Merchant is put on hold if a settlement failed
            // This is to avoid further failures on same merchant
            if ($this->source->getEntity() === Entity::SETTLEMENT)
            {
                $this->holdFunds = true;
            }
        }

        $this->repo->saveOrFail($this->fta);
    }

    protected function updateSourceEntity()
    {
        if ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId())
        {
            return;
        }

        $sourceStatus = $this->getSourceStatusFromReconEntityStatus();

        $this->source->setStatus($sourceStatus);
        $this->source->setUtr($this->fta->getUtr());
        $this->source->setRemarks($this->fta->getRemarks());

        if ($this->source->getEntity() !== Attempt\Type::REFUND)
        {
            $this->source->setFailureReason($this->fta->getFailureReason());
        }

        $this->source->saveOrFail();
    }

    protected function getSourceStatusFromReconEntityStatus(): string
    {
        $sourceEntityName = $this->source->getEntity();

        switch ($sourceEntityName)
        {
            case Entity::SETTLEMENT:
            case Entity::PAYOUT:
            case Entity::REFUND:
                return $this->getStatusForEntity($sourceEntityName);

            default:
                throw new Exception\LogicException('Unrecognized source entity: ' . $sourceEntityName);
        }
    }

    protected function getStatusForEntity(string $sourceEntityName): string
    {
        $entityStatusClass = $this->getEntityStatusNamespace($sourceEntityName);

        $attemptStatus = $this->fta->getStatus();

        switch ($attemptStatus)
        {
            case Attempt\Status::CREATED:
            case Attempt\Status::INITIATED:
                return $this->source->getStatus();

            case Attempt\Status::FAILED:
                return $entityStatusClass::FAILED;

            case Attempt\Status::PROCESSED:
                return $entityStatusClass::PROCESSED;

            default:
                throw new Exception\LogicException('Unrecognized attempt status: ' . $attemptStatus);
        }
    }

    protected function getEntityStatusNamespace(string $entityName): string
    {
        return Entity::getEntityNamespace($entityName) . '\\Status';
    }

    protected function isMerchantLevelError(): bool
    {
        $ftaStatus = $this->fta->getStatus();

        $utr = $this->fta->getUtr();

        $bankStatusCode = $this->fta->getBankStatusCode();

        if (($ftaStatus === Attempt\Status::FAILED) and
            (empty($utr) === false) and
            ($bankStatusCode === Status::PROCESSED))
        {
            return true;
        }

        return false;
    }
}