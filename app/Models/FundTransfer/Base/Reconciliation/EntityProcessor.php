<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer\Attempt;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;

abstract class EntityProcessor extends Base\Core
{
    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected $fta;

    protected $source;

    protected $sendFailureEmailToMerchant = false;

    /**
     * @var bool
     * Denotes if the webhook should be fired.
     */
    protected $fireWebhook = false;

    protected $holdFunds = false;

    protected $dashboardUrl;

    abstract protected function getAttemptStatus(): string;

    abstract protected function isMerchantLevelError(): bool;

    public function __construct(Attempt\Entity $fta)
    {
        parent::__construct();

        $this->fta = $fta;

        $this->source = $fta->source;

        $this->reconciledAt = Carbon::now(Timezone::IST)->timestamp;

        $this->dashboardUrl = $this->app['config']->get('applications.dashboard.url');
    }

    /**
     * Returns an array with the following 2 keys
     * - entity
     * - fire_webhook
     *
     */
    public function process(): array
    {
        $this->updateEntities();

        if ($this->sendFailureEmailToMerchant === true)
        {
            $this->sendReconciliationFailureEmail();
        }

        return [
            'entity'        => $this->source,
            'fire_webhook'  => $this->fireWebhook
        ];
    }

    protected function updateEntities()
    {
        $this->updateAttemptEntity();

        $this->updateSourceEntity();

        $this->updateMerchantEntity();

        $this->updateTransactionEntity();
    }

    final protected function updateAttemptEntity()
    {
        $status = $this->getAttemptStatus();

        $failureReason = ($status === Attempt\Status::FAILED) ? 'Reconciliation' : null;

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

    final protected function updateTransactionEntity()
    {
        $this->source->transaction->setReconciledAt($this->reconciledAt);

        $this->source->transaction->saveOrFail();
    }

    final protected function updateMerchantEntity()
    {
        if ($this->holdFunds === true)
        {
            $this->fta->merchant->setHoldFunds(true);

            $this->repo->saveOrFail($this->fta->merchant);
        }
    }

    final protected function getSourceStatusFromReconEntityStatus(): string
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

    final protected function getStatusForEntity(string $sourceEntityName): string
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

    final protected function getEntityStatusNamespace(string $entityName): string
    {
        return Entity::getEntityNamespace($entityName) . '\\Status';
    }

    protected function sendReconciliationFailureEmail()
    {
        if ($this->isMailEnabled() === false)
        {
            return;
        }

        $merchantId = $this->source->getMerchantId();

        $data['merchant_id'] = $merchantId;

        $data['remarks'] = $this->source->getRemarks();

        $data['profile_link'] = $this->dashboardUrl . '#/app/profile';

        // bankAccount for Settlelemt entity, and destination for Payout entity
        $ba = $this->source->destination ?? $this->source->bankAccount;

        $data['last4'] = $ba->getRedactedAccountNumber();

        $data['merchant_email'] = $this->source->merchant->getEmail();

        $data['subject'] = 'Razorpay | Notification for failed settlement on your account ' . $merchantId;

        $settlementFailureMail = new SettlementFailureMail($data);

        Mail::queue($settlementFailureMail);
    }

    protected function isMailEnabled(): bool
    {
        if ($this->source->merchant->isLinkedAccount() === true)
        {
            return false;
        }

        if ($this->app->environment('dev', 'testing') === true)
        {
            return true;
        }

        if ($this->mode === Mode::TEST)
        {
            return false;
        }

        if (Attempt\Type::isNotifyType($this->source->getEntity()) === false)
        {
            return false;
        }

        return true;
    }
}