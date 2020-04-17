<?php

namespace RZP\Models\FundTransfer\Attempt;

use Mail;
use Carbon\Carbon;
use Monolog\Logger;
use Razorpay\Trace\Logger as Trace;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Jobs\AttemptStatusCheck;
use RZP\Models\Settlement\Channel;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Mode as TransferMode;
use RZP\Models\FundTransfer\Mode as FundTransferMode;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Mail\Settlement\Reconciliation as ReconciliationEmail;
use RZP\Mail\Settlement\CriticalFailure as CriticalFailureEmail;

class BulkRecon extends Base\Core
{
    const MUTEX_RESOURCE = 'SETTLEMENT_RECONCILIATION_%s_%s';

    const DEFAULT_LIMIT = 1000;

    const MUTEX_LOCK_TIMEOUT = 300;

    protected $channel;

    protected $input;

    protected $allReconciledRows = [];

    protected $batchFundTransferStats = [];

    protected $notificationSummary = [];

    public function __construct(array $input, string $channel)
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->channel = $channel;

        $this->input = $input;
    }

    public function process()
    {
        (new Validator)->validateInput('bulk_reconcile', $this->input);

        $mutexResource = sprintf(self::MUTEX_RESOURCE, $this->channel, $this->mode);

        $data = $this->mutex->acquireAndRelease(
                    $mutexResource,
                    function ()
                    {
                        return $this->processEntities();
                    },
                    self::MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS);

        $this->sendReconciliationSummaryMail($data);

        $this->notifyCriticalErrors();

        return $data;
    }

    public function processIndividualEntity(Entity $fta)
    {
        $attempts = (new Base\PublicCollection)->push($fta);

        $channel = $fta->getChannel();

        (new Lock($channel))->acquireLockAndProcessAttempts(
            $attempts,
            function(Base\PublicCollection $collection)
            {
                return $this->initiateBulkReconProcess($collection);
            });

        //Removing slack alerts and mails for recon yesbank
        if((in_array($channel, Channel::getApiBasedChannels(), true) === false))
        {
            $this->notifyCriticalErrors();
        }

        $this->fireSettlementWebhook();
    }

    public function processEntities()
    {
        $limit = self::DEFAULT_LIMIT;

        list($from, $to) = $this->getTimestamps();

        if (isset($this->input['limit']) === true)
        {
            $limit = $this->input['limit'];
        }

        $attempts = $this->repo
                         ->fund_transfer_attempt
                         ->getAttemptsBetweenTimestampsWithStatus(
                             $this->channel,
                             Status::INITIATED,
                             $from,
                             $to,
                             $limit);

        (new Lock( $this->channel))->acquireLockAndProcessAttempts(
            $attempts,
            function(Base\PublicCollection $collection)
            {
                return $this->initiateBulkReconProcess($collection);
            });

        $summary = $this->getSummary();

        $this->fireSettlementWebhook();

        return $summary;
    }

    protected function initiateBulkReconProcess(Base\PublicCollection $attempts)
    {
        $ftaIds = $attempts->getIds();

        $relations = ['source', 'source.transaction', 'source.merchant' , 'batchFundTransfer'];

        $chunks = array_chunk($ftaIds, 1000);

        $entityProcessor = $this->getEntityProcessorClass($this->channel);

        $this->repo->transactionOnLiveAndTest(function() use ($ftaIds, $relations, $chunks, $entityProcessor)
        {
            try
            {
                foreach ($chunks as $ftaIds)
                {
                    $ftas = $this->repo->fund_transfer_attempt->findManyWithRelations($ftaIds, $relations);

                    foreach ($ftas as $fta)
                    {
                        try
                        {
                            $reconDetails = (new $entityProcessor($fta))->process();

                            $this->allReconciledRows[] = $reconDetails;

                            $entity = $reconDetails['entity'];

                            $this->updateBatchFundTransferStats($entity);

                            $this->updateCriticalErrorsSummary($fta);

                            $this->dispatchForStatusCheck($fta);
                        }
                        catch (\Throwable $e)
                        {
                            $this->trace->traceException(
                                $e,
                                Logger::ERROR,
                                TraceCode::FTA_RECON_FAILED,
                                [
                                    'fta_id' => $fta->getId(),
                                ]);
                        }
                    }
                }

                // Update batch stats post reconciliations
                $this->updateBatchProcess();
            }
            catch (\Throwable $e)
            {
                throw $e;
            }
        });
    }

    // This will dispatch the fta id for status check if the recon dint derive the final status for the attempt
    // It will only dispatch for status check only if the transfer mode is IMPS and status is initiated
    protected function dispatchForStatusCheck(Entity $attempt)
    {
        try
        {
            if (($attempt->getMode() !== TransferMode::IMPS) or ($attempt->getStatus() !== Status::INITIATED))
            {
                return;
            }

            $ageOfAttempt = Carbon::now(Timezone::IST)->getTimestamp() - $attempt->getCreatedAt();

            // If the attempt is more then 30 min old then don't dispatch for status check
            if ($ageOfAttempt >= Constants::MAX_AGE_ATTEMPT_STATUS_DISPATCH_AGE)
            {
                return;
            }

            AttemptStatusCheck::dispatch($this->mode, $attempt->getId())->delay(Constants::IMPS_STATUS_CHECK_DISPATCH_TIME);

            $this->trace->info(
                TraceCode::FTA_STATUS_CHECK_JOB_DISPATCHED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_STATUS_CHECK_DISPATCH_FAILED,
                [
                    'mode'   => $this->mode,
                    'fta_id' => $attempt->getId(),
                ]);
        }
    }

    protected function fireSettlementWebhook()
    {
        // Isolating the webhook flow in a try-catch, to keep the original settlement cycle unaffected
        try
        {
            (new FundTransferAttempt\Core)->notifyMerchantViaWebhook($this->allReconciledRows);
        }
        catch (\Throwable $e)
        {
            // Log only the entity ids instead of the entire entities
            $entityIds = array_map(function($reconciledRow)
            {
                return $reconciledRow['entity']->getId();
            }, $this->allReconciledRows);

            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::SETTLEMENT_PROCESSED_WEBHOOOK_FAILED,
                ['entities' => $entityIds]);
        }
    }

    protected function updateBatchProcess()
    {
        foreach ($this->batchFundTransferStats as $batchId => $attrs)
        {
            $batchEntity = $this->repo->batch_fund_transfer->findByPublicId($batchId);
            $batchEntity->setProcessedCount($attrs['processed_count']);
            $batchEntity->setProcessedAmount($attrs['processed_amount']);
            $batchEntity->saveOrFail();
        }
    }

    protected function notifyCriticalErrors()
    {
        if (empty($this->notificationSummary) === true)
        {
            return;
        }

        $sampleIds = array_slice($this->notificationSummary['ids'], 0, 5);

        $this->notificationSummary['settlement_Ids'] = 'Some of them are: ' . implode(',', $sampleIds);

        unset($this->notificationSummary['ids']);

        (new SlackNotification)->send('critical_failure', $this->notificationSummary, null, 1);

        $mail = new CriticalFailureEmail($this->notificationSummary);

        Mail::queue($mail);
    }

    protected function getTimestamps(): array
    {
        list($from, $to) = [null, null];

        if ((isset($this->input['from']) === true) and (isset($this->input['to']) === true))
        {
            $from = $this->input['from'];
            $to = $this->input['to'];
        }

        return [$from, $to];
    }

    protected function updateBatchFundTransferStats($reconciledEntity)
    {
        $entityStatusClass = EntityConstants::getEntityNamespace($reconciledEntity->getEntityName()) . '\\Status';

        if ($reconciledEntity->getStatus() !== $entityStatusClass::PROCESSED)
        {
            return;
        }

        if ($reconciledEntity->batchFundTransfer === null)
        {
            return;
        }

        $batchId = $reconciledEntity->batchFundTransfer->getId();

        $amount = $reconciledEntity->getAmount();

        if (isset($this->batchFundTransferStats[$batchId]) === false)
        {
            $this->batchFundTransferStats[$batchId] =
                ['processed_count' => 1, 'processed_amount' => $amount];
        }
        else
        {
            $this->batchFundTransferStats[$batchId]['processed_count']++;

            $this->batchFundTransferStats[$batchId]['processed_amount'] += $amount;
        }
    }

    protected function updateCriticalErrorsSummary(FundTransferAttempt\Entity $entity)
    {
        $default = [
            'channel' => $this->channel,
            'count'   => 0,
            'ids'     => []
        ];

        $statusClass = $this->getStatusClass($entity);

        $isCriticalError = $statusClass::isCriticalError($entity);

        if ($isCriticalError === false)
        {
            return;
        }

        $remark = $entity->getRemarks();

        if (($this->channel === Channel::ICICI) and
            ($entity->getMode() === FundTransferMode::RTGS))
        {
            $remark = $entity->getBankStatusCode();
        }

        $this->notificationSummary = (empty($this->notificationSummary) === true) ?
                                        $default : $this->notificationSummary;

        if (isset($this->notificationSummary[$remark]) === false)
        {
            $this->notificationSummary[$remark] = 0;
        }

        $this->notificationSummary['count']++;

        $this->notificationSummary['ids'][] = $entity->source->getId();

        $this->notificationSummary[$remark]++;
    }

    protected function getStatusClass(FundTransferAttempt\Entity $entity)
    {
        $channel = $entity->getChannel();

        if ($entity->hasVpa() === true)
        {
            return '\\RZP\\Models\\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\GatewayStatus';
        }

        return "RZP\\Models\\FundTransfer\\" . ucfirst($channel) . "\\Reconciliation\\Status";
    }

    protected function getEntityProcessorClass(string $channel)
    {
        return '\\RZP\\Models\FundTransfer\\' . ucfirst($channel) . '\\Reconciliation\\EntityProcessor';
    }

    protected function getSummary(): array
    {
        $failureEntityIds = $successEntityIds = $allEntityIds = [];
        $failureEntities = new Base\PublicCollection;

        $settlementsCount = 0;

        foreach ($this->allReconciledRows as $reconciledRow)
        {
            $entity = $reconciledRow['entity'];

            $entityId = $entity->getId();

            $allEntityIds[] = $entityId;

            $method = 'isStatusFailed';

            if (method_exists($entity, 'isStatusReversedOrFailed') === true)
            {
                $method = 'isStatusReversedOrFailed';
            }

            if ($entity->$method() === true)
            {
                $failureEntities[] = $entity;
            }
            else
            {
                $successEntityIds[] = $entityId;
            }

            if ($entity->getEntityName() === EntityConstants::SETTLEMENT)
            {
                $settlementsCount++;
            }
        }

        // Get distinct entity ids in all array.
        // There will be duplicates in case of same day retry
        // Ideally there shouldn't be duplicates in success, but we do a defensive unique
        $allEntityIds = array_unique($allEntityIds);
        $successEntityIds = array_unique($successEntityIds);

        $failureEntities = $failureEntities->uniqueStrict(function ($entity)
        {
            return $entity->getId();
        });

        foreach ($failureEntities as $entity)
        {
            $failureEntityIds[] = $entity->getId();
        }

        // If multiple, let's say 2, attempts were made, on the same day for a settlement,
        // the recon file would have both failure and success rows corresponding to each
        // attempt. In this case the settlement corresponding to them would be part of
        // both successEntities, and failureEntities. To avoid a false alarm for this
        // settlement, we do this
        $failureEntityIds = array_diff($failureEntityIds, $successEntityIds);

        $failureCount = count($failureEntityIds);

        $summary = [
            'channel'                       => $this->channel,
            'total_count'                   => count($allEntityIds),
            'failures_count'                => $failureCount,
            'settlement_failure_amount'     => 0,
        ];

        $settlementsFailureIds = [];

        foreach ($failureEntities as $entity)
        {
            $entityName = $entity->getEntityName();

            if ($entityName === EntityConstants::SETTLEMENT)
            {
                $settlementsFailureIds[] = $entity->getId();

                $summary['settlement_failure_amount'] += $entity->getAmount();
            }

            $key = $entityName. '_failure_count';

            // Adds keys settlement_failure_count, payout_failure_count, refund_failure_count to summary
            $summary[$key] = ($summary[$key] ?? 0) + 1;
        }

        // if no settlement entities were marked failed
        if (isset($summary['settlement_failure_count']) === false)
        {
            return $summary;
        }

        //
        // Get slack summary for settlement failures
        //
        $settlementsFailureCount = $summary['settlement_failure_count'];

        if ($settlementsCount === $settlementsFailureCount)
        {
            $failureRemark = 'All settlements failed.';
        }
        else
        {
            $failureRemark = $settlementsFailureCount . ' settlement(s) failed.';
        }

        if ($settlementsFailureCount > 5)
        {
            $settlementsFailureIds = array_slice($settlementsFailureIds, 0, 5, true);

            $failureIdMsg = ' A few failed settlement IDs: ';
        }
        else
        {
            $failureIdMsg = ' Settlement IDs: ';
        }

        $summary['settlement_failure_remarks'] = $failureRemark;

        $summary['settlement_failure_ids'] = $failureIdMsg . implode(', ', $settlementsFailureIds);

        return $summary;
    }

    protected function sendReconciliationSummaryMail($response)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $msg = 'Bulk Reconcilaition done.' . PHP_EOL;

        $failureCount = $response['failures_count'];

        $msg .= 'Failure Count: ' . $failureCount . PHP_EOL;

        #TODO:: What date to put here?
        $this->date = Carbon::today(Timezone::IST)->format('d-m-Y');

        $data = [
            'date'    => $this->date,
            'body'    => $msg,
            'channel' => $this->channel
        ];

        $email = new ReconciliationEmail($data);

        Mail::queue($email);
    }
}
