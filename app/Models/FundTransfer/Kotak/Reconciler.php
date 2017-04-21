<?php

namespace RZP\Models\FundTransfer\Kotak;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Excel;
use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\SlackNotification;

use Illuminate\Support\Facades\App;

class Reconciler
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    const MUTEX_RESOURCE        = 'SETTLEMENT_RECONCILIATION_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 300;

    const SOURCE_ATTRS = [
        Settlement\Entity::UTR,
        Settlement\Entity::STATUS,
        Settlement\Entity::FAILURE_REASON,
        Settlement\Entity::REMARKS,
    ];

    const CHILD_ATTRS = [
        FundTransferAttempt\Entity::UTR,
        FundTransferAttempt\Entity::STATUS,
        FundTransferAttempt\Entity::FAILURE_REASON,
        FundTransferAttempt\Entity::REMARKS,
        FundTransferAttempt\Entity::BANK_STATUS_CODE,
        FundTransferAttempt\Entity::DATE_TIME,
        FundTransferAttempt\Entity::CMS_REF_NO,
    ];

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected $batchFundTransferStats = [];

    protected $app;

    protected $repo;

    protected $trace;

    public function __construct()
    {
        $this->reconciledAt = time();

        $this->app = App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $this->trace = $this->app['trace'];

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
                [
                    'message' => 'No file present'
                ]);

            return [];
        }

        $data = $this->parseTextFile($reconcileFile);

        $response = null;

        if (empty($data) === true)
        {
            $response =  [
                'message' => 'no records to reconcile'
            ];
        }
        else
        {
            $date = Carbon::createFromFormat('d-M-y', $data[0][Headings::PAYMENT_DATE]);

            // update the format so that recon mail is appended to settlement mail
            $date = $date->format('d-m-Y');

            $response = $this->reconcile($data);

            $this->storeReconciledFile($reconcileFile);

            $this->sendReconciliationMail($date, $response);
        }

        return $response;
    }

    protected function reconcile($data): array
    {
        $collection = new Base\PublicCollection;
        $failures = new Base\PublicCollection;

        $this->repo->beginTransaction();

        $unprocessedFailedIds = [];

        try
        {
            foreach ($data as $row)
            {
                $data = $this->reconcileEntity($row);

                $entity = $data['entity'];

                if ($data['entity'] === null)
                {
                    $unprocessedFailedIds[] = $data['entity_id'] ?? 'null';

                    continue;
                }

                $collection->push($entity);

                if ($entity->isStatusFailed())
                {
                    $failures->push($entity);
                }
            }
            foreach ($this->batchFundTransferStats as $batchId => $attrs)
            {
                $this->repo->batch_fund_transfer->updateBatch($batchId, $attrs);
            }

            $this->repo->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->rollback();

            (new SlackNotification)->failure('setl_reconciliation', $e);

            throw $e;
        }

        $failureIds = implode(',', $failures->getPublicIds());

        $processingFailedIds = implode(',', $unprocessedFailedIds);

        $response = [
            'total_count'               => $collection->count(),
            'failures_count'            => $failures->count(),
            'failure ids'               => $failureIds,
            'processing failed ids'     => $processingFailedIds,
        ];

        (new SlackNotification)->success('setl_reconciliation', $response);

        return $response;
    }

    protected function reconcileEntity($row): array
    {
        $parsedData = $this->parseDataFromRow($row);

        $data = $this->loadEntityAndRelations($parsedData);

        $entity = $data['entity'];
        $entityId = $data['entity_id'];

        if ($entity !== null)
        {
            $entity = $this->processEntityStatus($entity, $parsedData);
        }

        return [
            'entity_id' => $entityId,
            'entity'    => $entity
        ];
    }

    protected function processEntityStatus($entity, $parsedData)
    {
        $recordDate = Carbon::createFromFormat('d-M-y', $parsedData['payment_date'], 'Asia/Kolkata');

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $tenPm = $recordDate->hour(22)->timestamp;

        $failureReason = null;

        $class = get_class($entity);
        $class = str_replace('\\Entity', '\\Status', $class);

        $status = $class::FAILED;

        if ($parsedData['bank_status_code'] === Status::PROCESSED)
        {
            $remarks = $parsedData['remarks'];

            // If current time is before 10 pm, dont mark the settlement as
            // processed and update only the utr
            if (($now < $tenPm) and ($this->getMode() === Mode::LIVE))
            {
                $status = $entity->getStatus();
            }
            else if ((empty($remarks) === true) or
                (in_array($remarks, self::SUCCESS_STATUS) === true))
            {
                $status = $class::PROCESSED;
            }
            else
            {
                $status = $class::FAILED;

                $failureReason = 'Reconciliation';
            }
        }

        $parsedData['failure_reason'] = $failureReason;

        $parsedData['status'] = $status;

        $source = ($parsedData['version'] === 'V2') ? $entity->source : $entity;

        $this->updateEntities($parsedData, $source, $entity);

        return $source;
    }

    protected function updateEntities(array $parsedData, $source, $entity)
    {
        $oldStatus = $entity->getStatus();

        $status = $parsedData['status'];

        if ($oldStatus !== $status)
        {
            // if already processed
            if ($entity->isPendingReconciliation() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Entity Id: ' . $entity->getPublicId());
            }

            // Update processed stats in batch entity
            if ($status === FundTransferAttempt\Status::PROCESSED)
            {
                $batchId = $entity->batchFundTransfer->getId();

                $this->updateBatchFundTransferStats($batchId, $source);
            }
        }

        // Update source and child entities
        if ($parsedData['version'] === 'V2')
        {
            foreach (self::CHILD_ATTRS as $type => $attr)
            {
                $functionName = 'set' . studly_case($attr);
                $entity->$functionName($parsedData[$attr]);
            }

            $this->repo->saveOrFail($entity);
        }

        foreach (self::SOURCE_ATTRS as $type => $attr)
        {
            $functionName = 'set' . studly_case($attr);
            $source->$functionName($parsedData[$attr]);
        }

        $this->repo->saveOrFail($source);

        $source->transaction->setReconciledAt($this->reconciledAt);
        $this->repo->saveOrFail($source->transaction);

        return $source;
    }

    /**
     * Reads row from reconciliation file, and returns array of parsed data from that
     *
     * @param           Entity
     * @param   Array   Row to be parsed
     *
     * @return  Array   Parsed data
     */
    protected function parseDataFromRow(array $row): array
    {
        $version = $row[Headings::VERSION] ?: FundTransferAttempt\Version::V1;
        FundTransferAttempt\Version::validateVersion($version);

        $statusCode = trim($row[Headings::STATUS_OF_TRANSACTION] ?? null);

        $utr = null;

        if ($statusCode === Status::PROCESSED)
        {
            $utr = trim($row['UTR number']);
            $utr = ($utr === '') ? null : $utr;
        }

        return [
            'version' => $version,
            'payment_ref_no' => trim($row[Headings::PAYMENT_REF_NO] ?? null),
            'utr' => $utr,
            'bank_status_code' => $statusCode,
            'remarks' => trim($row[Headings::REMARKS] ?? null),
            'payment_date' => trim($row[Headings::PAYMENT_DATE] ?? null),
            'date_time' => trim($row[Headings::DATE_TIME] ?? null),
            'cms_ref_no' => trim($row[Headings::CMS_REF_NO] ?? null),
        ];
    }

    protected function loadEntityAndRelations(array $parsedData): array
    {
        $entityId = $parsedData['payment_ref_no'];

        $entityId = str_replace(' ', '_', $entityId);

        if (($entityId === '') and (strlen(trim((implode($parsedData)))) === 0))
        {
                return;
        }

        $entity = null;

        switch ($parsedData['version'])
        {
            case 'V2':
                $entity = $this->repo
                               ->fund_transfer_attempt
                               ->findWithRelations(
                                    $entityId,
                                    ['source', 'source.transaction', 'source.merchant']);
                break;

            case 'V1':
                if (strpos($entityId, Settlement\Entity::getSign(), 0) === 0)
                {
                    Settlement\Entity::verifyIdAndStripSign($entityId);

                    $entity = $this->repo
                                   ->settlement
                                   ->findWithRelations($entityId, ['merchant', 'transaction']);
                }
                else if(strpos($entityId, Payout\Entity::getSign(), 0) === 0)
                {
                    Payout\Entity::verifyIdAndStripSign($entityId);

                    $entity = $this->repo
                                   ->payout
                                   ->findWithRelations($entityId, ['merchant', 'transaction']);
                }
                break;

            default:
                throw new Exception\LogicException('Version not supported: ' . $parsedData['version']);
        }

        return [
            'entity_id' => $entityId,
            'entity'    => $entity
        ];
    }

    protected function updateBatchFundTransferStats($batchId, $source)
    {
        if (isset($this->batchFundTransferStats[$batchId]) === false)
        {
            $this->batchFundTransferStats[$batchId] =
                ['processed_count' => 1, 'processed_amount' => $source->getAmount()];
        }
        else
        {
            $this->batchFundTransferStats[$batchId]['processed_count']++;

            $this->batchFundTransferStats[$batchId]['processed_amount'] += $source->getAmount();
        }
    }

    protected function sendReconciliationMail($date, $response)
    {
        $msg = 'UTR File reconciled.' . PHP_EOL;

        $failureCount = $response['failures_count'];

        $msg .= 'Failure Count: ' . $failureCount . PHP_EOL;

        if ($failureCount !== 0)
        {
            $msg .= 'Failed settlement ids: ' . $response['failure ids'];
        }

        $data['subject'] = "Re: Kotak Settlement files for $date";
        $data['date'] = $date;
        $data['body'] = $msg;

        Mail::queue('emails.message', $data, function($message) use ($data)
        {
            $emails = ['settlements@razorpay.com'];

            $message->from('settlement@razorpay.com', 'Kotak Settlement');

            $message->subject($data['subject']);

            $message->to($emails);

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_BENEFICIARY_MAIL);
        });
    }

    public static function getHeadings()
    {
        return Headings::getResponseFileHeadings();
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
