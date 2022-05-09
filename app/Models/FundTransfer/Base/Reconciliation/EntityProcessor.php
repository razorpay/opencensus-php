<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Diag\EventCode;
use RZP\Models\FundTransfer\Attempt\Type;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Yesbank\Reconciliation\GatewayStatus;
use RZP\Mail\Merchant\SettlementFailure as SettlementFailureMail;

/**
 * @property Attempt\Core core
 */
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

        $this->core = new Attempt\Core;

        $this->source = $fta->source;

        $this->reconciledAt = Carbon::now(Timezone::IST)->timestamp;

        $this->dashboardUrl = $this->app['config']->get('applications.dashboard.url');
    }

    /**
     * Returns an array with the following 2 keys
     * - entity
     * - fire_webhook
     */
    public function process(): array
    {
        $this->updateEntities();

        if ($this->sendFailureEmailToMerchant === true)
        {
            $this->sendReconciliationFailureEmail();
        }

        return [
            'entity'            => $this->source,
            'fire_webhook'      => $this->fireWebhook,
            'send_fail_sms'     => $this->sendFailureEmailToMerchant,
        ];
    }

    protected function updateEntities()
    {
        // All of these are in a single DB transaction.

        $this->updateAttemptEntity();

        if (($this->fta->getSourceType() !== Type::REFUND) and
            ($this->source->getBatchFundTransferId() !== $this->fta->getBatchFundTransferId()))
        {
            return;
        }

        $this->core->updateSourceEntity($this->fta);

        $this->core->updateMerchantEntity($this->fta, $this->holdFunds);
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

        $batchFta = $this->fta->batchFundTransfer;

        $batchFtaId = null;

        //BatchFTA can be null in test mode
        if (empty($batchFta) === false)
        {
            $batchFtaId = $batchFta->getId();
        }

        $customProperties = [
            'merchant_id'                       => $this->fta->merchant->getId(),
            'channel'                           => $this->fta->getChannel(),
            'purpose'                           => $this->fta->getPurpose(),
            'fund_transfer_attempt_id'          => $this->fta->getId(),
            'batch_fund_transfer_attempt_id'    => $batchFtaId,
            'utr'                               => $this->fta->getUtr(),
            'source_type'                       => $this->fta->getSourceType(),
            'fund_transfer_attempt_mode'        => $this->fta->getMode(),
            'fund_transfer_attempt_status'      => $this->fta->getStatus(),
            'source_id'                         => $this->fta->getSourceId(),
            'error_message'                     => $failureReason,
        ];

        $this->app['diag']->trackSettlementEvent(
            EventCode::FTA_STATUS_UPDATED,
            null,
            null,
            $customProperties);

        $this->fta->setStatus($status);

        //
        // Set the fire webhook flag as true only for the settlements that
        // have to be updated in the current settlement cycle. For the settlements
        // that have been settled in the previous cycles, this flag stays false.
        //
        $this->fireWebhook = true;

        if ($this->fta->isStatusFailed() === true)
        {
            $this->trace->info(TraceCode::FTA_STATUS_FAILED, [
                'fta_id'      => $this->fta->getId(),
                'merchant_id' => $this->fta->getMerchantId(),
            ]);

            $failureBucket = Attempt\Metric::RZP_ERROR;

            if ($this->isMerchantLevelError() === true)
            {
                $this->trace->info(
                    TraceCode::FTA_STATUS_FAILED_MERCHANT_ERROR, [
                        'fta_id'      => $this->fta->getId(),
                        'merchant_id' => $this->fta->getMerchantId(),
                    ]);

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

    protected function getAttemptStatus(): array
    {
        $status = $this->fta->getStatus();

        $bankStatusCode = $this->fta->getBankStatusCode();

        $bankResponseCode = $this->fta->getBankResponseCode();

        $utr = $this->fta->getUtr();

        $failureReason  = null;

        $statusNamespace = $this->core->getStatusClass($this->fta);

        $statusClass = new $statusNamespace;

        $successStatuses = $statusClass::getSuccessfulStatus();

        $failureStatuses = $statusClass::getFailureStatus();

        $isSuccess = $statusClass::inStatus($successStatuses, $bankStatusCode, $bankResponseCode);

        $isFailure = $statusClass::inStatus($failureStatuses, $bankStatusCode, $bankResponseCode);

        if (($isSuccess === true) and
            (empty($utr) === false))
        {
            $status = Attempt\Status::PROCESSED;
        }
        else if ($isFailure === true)
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

    protected function getMerchantEmail(Merchant\Entity $merchant): string
    {
        if ($merchant->isLinkedAccount() === true)
        {
            return $merchant->parent->getEmail();
        }

        return $merchant->getEmail();
    }
}
