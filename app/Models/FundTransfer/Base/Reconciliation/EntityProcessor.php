<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;
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
        // All of these are in a single DB transaction.

        $this->updateAttemptEntity();

        if ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId())
        {
            return;
        }

        $this->updateSourceEntity();

        $this->updateMerchantEntity();

        $this->updateTransactionEntity();
    }

    protected function updateAttemptEntity()
    {
        list($status, $failureReason) = $this->getAttemptStatus();

        // Verify status
        $oldStatus = $this->fta->getStatus();

        if ($oldStatus === $status)
        {
            return;
        }

        // If the old and new status do not match
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

        if ($this->fta->isStatusFailed() === true)
        {
            $this->trace->info(TraceCode::FTA_STATUS_FAILED, ['fta_id' => $this->fta->getId()]);

            $failureBucket = Attempt\Metric::RZP_ERROR;

            if ($this->isMerchantLevelError() === true)
            {
                $this->trace->info(
                    TraceCode::FTA_STATUS_FAILED_MERCHANT_ERROR, ['fta_id' => $this->fta->getId()]);

                $this->sendFailureEmailToMerchant = true;

                $failureBucket = Attempt\Metric::MERCHANT_ERROR;

                // Merchant is put on hold if a settlement failed
                // This is to avoid further failures on same merchant
                if ($this->source->getEntity() === Entity::SETTLEMENT)
                {
                    $this->holdFunds = true;
                }
            }

            $this->trace->count(
                Attempt\Metric::ATTEMPTS_FAILED_TOTAL,
                [
                    Attempt\Metric::CHANNEL             => $this->fta->getChannel(),
                    Attempt\Metric::SOURCE_TYPE         => $this->fta->getSourceType(),
                    Attempt\Metric::BANK_STATUS_CODE    => $this->fta->getBankStatusCode(),
                    Attempt\Metric::FAILURE_BUCKET      => $failureBucket
                ],
                1);
        }

        $this->repo->saveOrFail($this->fta);
    }

    protected function updateSourceEntity()
    {
        $statusNamespace = $this->getStatusClass($this->fta);

        $statusClass = new $statusNamespace;

        $isInternalError = $statusClass::isCriticalError($this->fta);

        $bankStatusCode = $this->fta->getBankStatusCode();

        $publicErrorMessage = $statusClass::getPublicFailureReason($bankStatusCode);

        $ftaData = [
            'bank_account_id'   => $this->fta->getBankAccountId(),
            'vpa_id'            => $this->fta->getVpaId(),
            'merchant_id'       => $this->fta->getMerchantId(),
            'fta_id'            => $this->fta->getId(),
            'source_id'         => $this->source->getId(),
            'beneficiary_name'  => null,
            'utr'               => $this->fta->getUtr(),
            'mode'              => $this->fta->getMode(),
            'remarks'           => $this->fta->getRemarks(),
            'fta_status'        => $this->fta->getStatus(),
            'bank_status_code'  => $bankStatusCode,
            'internal_error'    => $isInternalError,
            'failure_reason'    => $publicErrorMessage,
        ];

        $this->postFtaRecon($this->source, $ftaData);
    }

    protected function updateTransactionEntity($reconciledType = ReconciledType::MIS)
    {
        // Source entity might update the transaction but because we would have already fetched
        // the transaction from source earlier. Then if we try to access $this->source->transaction now,
        // It will return an old copy. Not the updated transaction. Hence, we reload the relation.
        $this->source->load(Entity::TRANSACTION);

        $this->source->transaction->setReconciledAt($this->reconciledAt);

        $this->source->transaction->setReconciledType($reconciledType);

        $this->source->transaction->saveOrFail();
    }

    protected function updateMerchantEntity()
    {
        if ($this->holdFunds === true)
        {
            $this->fta->merchant->setHoldFunds(true);

            $this->repo->saveOrFail($this->fta->merchant);
        }
    }

    protected function getAttemptStatus(): array
    {
        $status = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        $utr = $this->fta->getUtr();

        $failureReason  = null;

        //
        // For Yesbank VPA, we want to reconcile only if the status_code
        // is either success or failure. Many times, we get `pending` or `timeout`.
        // In these cases, since we anyway don't know the status, it does not make
        // sense for us to reconcile these, or check the error codes and stuff.
        //
        if (($this->fta->getChannel() === Channel::YESBANK) and
            ($this->fta->hasVpa() === true))
        {
            $statusCode = $this->fta->getBankResponseCode();

            if (($statusCode !== GatewayStatus::STATUS_CODE_SUCCESS) and
                ($statusCode !== GatewayStatus::STATUS_CODE_FAILURE))
            {
                return [$status, $failureReason];
            }
        }

        $statusNamespace = $this->getStatusClass($this->fta);

        $statusClass = new $statusNamespace;

        $successStatuses = $statusClass::getSuccessfulStatus();

        $failureStatuses = $statusClass::getFailureStatus();

        if ((in_array($bankStatusCode, $successStatuses, true) === true) and
            (empty($utr) === false))
        {
            $status = Attempt\Status::PROCESSED;
        }
        else if (in_array($bankStatusCode, $failureStatuses, true) === true)
        {
            $status = Attempt\Status::FAILED;

            $failureReason = 'Reconciliation';
        }

        return [$status, $failureReason];
    }

    protected function getStatusForEntity(string $sourceEntityName, string $attemptStatus): string
    {
        $entityStatusClass = $this->getEntityStatusNamespace($sourceEntityName);

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

    protected function sendReconciliationFailureEmail()
    {
        try
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
            $ba = $this->source->destination ?? $this->source->bankAccountForFundTransferRecon;

            $data['last4'] = $ba->getRedactedAccountNumber();

            $data['merchant_email'] = $this->getMerchantEmail($this->source->merchant);

            $data['subject'] = 'Razorpay | Notification for failed settlement on your account ' . $merchantId;

            $settlementFailureMail = new SettlementFailureMail($data);

            Mail::queue($settlementFailureMail);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::FUND_TRANSFER_RECON_EMAIL_FAILED,
                [
                    'fta' => $this->fta->getId()
                ]
            );
        }
    }

    protected function isMailEnabled(): bool
    {
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

    protected function getStatusClass(Attempt\Entity $fta)
    {
        $channel = $fta->getChannel();

        if ($fta->hasVpa() === true)
        {
             return '\\RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\GatewayStatus';
        }

        return 'RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\Status';
    }

    protected function getMerchantEmail(Merchant\Entity $merchant): string
    {
        if ($merchant->isLinkedAccount() === true)
        {
            return $merchant->parent->getEmail();
        }

        return $merchant->getEmail();
    }

    /**
     * @param       $source
     * @param array $ftaData
     */
    protected function postFtaRecon($source, array $ftaData)
    {
        $this->trace->info(
            TraceCode::FTA_SOURCE_PROCESSING_DATA,
            $ftaData);

        try
        {
            $entityType = $source->getEntity();

            $sourceCoreClass = Entity::getEntityNamespace($entityType) . '\\Core';

            $sourceCore = new $sourceCoreClass();

            if (method_exists($sourceCore, 'updateStatusAfterFtaRecon') === false)
            {
                return;
            }

            $sourceCore->updateStatusAfterFtaRecon($source, $ftaData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FTA_SOURCE_PROCESSING_FAILED,
                $ftaData
            );
        }
    }
}
