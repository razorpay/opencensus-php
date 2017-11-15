<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Mail;
use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Kotak;
use Razorpay\Trace\Logger as Trace;
use RZP\Mail\Settlement as SettlementMail;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;

class Processor extends Base\Core
{
    use Kotak\FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    const MUTEX_RESOURCE = 'SETTLEMENT_RECONCILIATION_PROCESSING';

    const MUTEX_LOCK_TIMEOUT = 300;

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    /**
     * Array of all entities fetched for all the rows in the file
     */
    protected $allEntities = [];

    /**
     * Array of ids for which entity couldn't be found in database
     */
    protected $unprocessedIds = [];

    protected $date;

    protected $batchFundTransferStats = [];

    public function __construct()
    {
        parent::__construct();

        $this->reconciledAt = time();

        $this->mutex = $this->app['api.mutex'];
    }

    public function process($input)
    {
        $data = $this->mutex->acquireAndRelease(
            self::MUTEX_RESOURCE,
            function () use ($input)
            {
                return $this->processReconciliation($input);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_SETTLEMENT_RECONCILIATION_IN_PROGRESS);

        return $data;
    }

    public function processReconciliation($input)
    {
        $reconcileFile = $this->getReconcilationFile($input);

        if ($reconcileFile === null)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                ['message' => 'No file present']);

            return [];
        }

        $data = $this->parseTextFile($reconcileFile);

        $response = null;

        if (empty($data) === true)
        {
            $response = ['message' => 'no records to reconcile'];
        }
        else
        {
            $date = Carbon::createFromFormat('d-M-y', $data[0][Kotak\Headings::PAYMENT_DATE]);

            // update the format so that recon mail is appended to settlement mail
            $this->date = $date->format('d-m-Y');

            $response = $this->startReconciliation($data);

            $this->storeReconciledFile($reconcileFile);

            $this->sendReconciliationSummaryMail($response);
        }

        return $response;
    }

    protected function startReconciliation($data): array
    {
        $summary = $this->repo->transactionOnLiveAndTest(function() use ($data)
        {
            $webhookData = [];

            try
            {
                foreach ($data as $row)
                {
                    $reconciledRowDetails = $this->reconcileEntity($row);

                    $webhookData[] = $reconciledRowDetails;

                    $entity = $reconciledRowDetails['entity'];

                    if ($entity === null)
                    {
                        $this->unprocessedIds[] = $row[Kotak\Headings::PAYMENT_REF_NO] ?? 'null';
                    }
                    else
                    {
                        $this->allEntities[] = $entity;

                        $this->updateBatchFundTransferStats($entity);
                    }
                }

                // Update batch stats post reconciliations
                foreach ($this->batchFundTransferStats as $batchId => $attrs)
                {
                    $batchEntity = $this->repo->batch_fund_transfer->findByPublicId($batchId);
                    $batchEntity->setProcessedCount($attrs['processed_count']);
                    $batchEntity->setProcessedAmount($attrs['processed_amount']);
                    $batchEntity->saveOrFail();
                }
            }
            catch (\Exception $e)
            {
                (new SlackNotification)->failure('setl_reconciliation', $e);

                // Empty the array to not trigger the webhook
                $webhookData = [];

                throw $e;
            }

            $summary = $this->getSummary();

            (new SlackNotification)->success('setl_reconciliation', $summary);

            // Isolating the webhook flow in a try-catch, to keep the original settlement cycle unaffected
            try
            {
                (new FundTransferAttempt\Core)->notifyMerchantViaWebhook($webhookData);
            }
            catch (\Exception $e)
            {
                // Log only the entity ids instead of the entire entities
                $entities = array_map(function($entity) {
                    return $entity->getId();
                }, $this->allEntities);

                $this->trace->traceException(
                    $e,
                    Trace::CRITICAL,
                    TraceCode::SETTLEMENT_PROCESSED_WEBHOOOK_FAILED,
                    ['entities' => $entities]);
            }

            return $summary;
        });

        return $summary;
    }

    /**
     * Reconciles the entity.
     *
     * @param $row
     *
     * @return array
     */
    protected function reconcileEntity($row): array
    {
        $version = $this->getSettlementVersion($row);

        $versionRowProcessorClass = 'RZP\\Models\\FundTransfer\\Kotak\\Reconciliation\\' .
                                    ucwords($version) .
                                    '\\RowProcessor';

        $reconciledRowDetails = (new $versionRowProcessorClass($row))->process($this->reconciledAt);

        return $reconciledRowDetails;
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

    /**
     * Reads row from reconciliation file, and returns array of parsed data from that
     *
     * @param           Entity
     * @param   Array   Row to be parsed
     *
     * @return  Array   Parsed data
     */
    protected function getSettlementVersion(array $row): string
    {
        $version = FundTransferAttempt\Version::V1;

        if (Kotak\Reconciliation\V2\RowProcessor::isV2($row) === true)
        {
            $version = FundTransferAttempt\Version::V2;
        }
        else if (Kotak\Reconciliation\V3\RowProcessor::isV3($row) === true)
        {
            $version = FundTransferAttempt\Version::V3;
        }

        return $version;
    }

    protected function getSummary(): array
    {
        $failureEntityIds = $successEntityIds = $allEntityIds = [];
        $failureEntities = new Base\PublicCollection;

        foreach ($this->allEntities as $entity)
        {
            $entityId = $entity->getId();

            $allEntityIds[] = $entityId;

            if ($entity->isStatusFailed())
            {
                $failureEntities[] = $entity;
            }
            else
            {
                $successEntityIds[] = $entityId;
            }
        }

        // Get distinct entity ids in all array.
        // There will be duplicates in case of same day retry
        // Ideally there shouldn't be duplicates in success, but we do a defensive unique
        $allEntityIds = array_unique($allEntityIds);
        $successEntityIds = array_unique($successEntityIds);

        $failureEntities = $failureEntities->uniqueStrict(function ($entity) {
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

        $failureAmount = 0;

        foreach ($failureEntities as $entity)
        {
            if (in_array($entity->getId(), $failureEntityIds, true) === true)
            {
                $failureAmount += $entity->getAmount();
            }
        }

        $failureAmount = $failureAmount / 100;

        $totalCount = count($allEntityIds);
        $failureCount = count($failureEntityIds);

        $summary = [
            'total_count'               => $totalCount,
            'unprocessed_ids'           => implode(', ', $this->unprocessedIds),
            'failures_count'            => $failureCount,
            'failure_amount (in Rs.)'   => $failureAmount,
            'failure ids'               => $failureEntityIds,
        ];

        if ($failureCount > 0)
        {
            $this->trace->error(
                TraceCode::SETTLEMENT_RECONCILIATION_FAILED, $summary);

            if ($totalCount === $failureCount)
            {
                $failureRemark = 'All settlements failed.';
            }
            else
            {
                $failureRemark = $failureCount . ' settlement(s) failed.';
            }

            if ($failureCount > 5)
            {
                $failureEntityIds = array_slice($failureEntityIds, 0, 5, true);

                $failureIdMsg = ' A few failed settlement IDs: ';
            }
            else
            {
                $failureIdMsg = ' Settlement IDs: ';
            }

            $summary['failure remarks'] = $failureRemark;

            $summary['failure ids'] = $failureIdMsg . implode(', ', $failureEntityIds);
        }

        return $summary;
    }

    protected function sendReconciliationSummaryMail($response)
    {
        if (($this->mode === Mode::TEST) and
            ($this->app->environment('dev', 'testing') === false))
        {
            return;
        }

        $msg = 'UTR File reconciled.' . PHP_EOL;

        $failureCount = $response['failures_count'];

        $msg .= 'Failure Count: ' . $failureCount . PHP_EOL;

        if ($failureCount !== 0)
        {
            $msg .= $response['failure ids'];
        }

        $data['date'] = $this->date;
        $data['body'] = $msg;

        $kotakReconciliationMail = new SettlementMail\KotakReconciliation($data);

        Mail::queue($kotakReconciliationMail);
    }

    public static function getHeadings()
    {
        return Kotak\Headings::getResponseFileHeadings();
    }

    protected function getReconcilationFile($input)
    {
        $reconcileFile = null;

        if ((isset($input['source']) === true) and
            ($input['source'] === 'lambda'))
        {
            $key = $input['key'];

            $reconcileFile = $this->getH2HFileFromAws($key);
        }
        else
        {
            $reconcileFile = $this->getFile($input);
        }

        return $reconcileFile;
    }

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix): array
    {
        $count = count($values);

        $this->trace->info(TraceCode::MISC_TRACE_CODE, ['count' => $count]);

        if (($count < 54) or ($count > 55))
        {
            throw new Exception\LogicException(
                'Invalid count: ' . $count . ' Should be either 54 or 55.');
        }

        $headings = array_slice($headings, 0, $count);

        $values = array_combine($headings, $values);

        return $values;
    }
}
