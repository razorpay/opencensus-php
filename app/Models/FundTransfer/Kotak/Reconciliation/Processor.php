<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Excel;
use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\SlackNotification;

use Illuminate\Support\Facades\App;

class Processor
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
            $response = ['message' => 'no records to reconcile'];
        }
        else
        {
            $date = Carbon::createFromFormat('d-M-y', $data[0][Kotak\Headings::PAYMENT_DATE]);

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
                $batchEntity = $this->repo->batch_fund_transfer->findByPublicId($batchId);
                $batchEntity->setProcessedCount();
                $batchEntity->setProcessedAmount();
                $batchEntity->saveOrFail();
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
        $version = $this->getRowVersion($row);

        $className = ucwords($version);

        $classNamespace = 'RZP\\Models\\FundTransfer\\Kotak\\Reconciliation\\RowProcessor\\' . $className;

        $reconciledEntity = (new $classNamespace($row))->process($this->reconciledAt);

        $this->updateBatchFundTransferStats($reconciledEntity);
    }

    protected function updateBatchFundTransferStats($reconciledEntity)
    {
        $entityStatusClass = EntityConstants::getEntityNamespace($reconciledEntity->getEntityName()) . '\\Status';

        if ($reconciledEntity->getStatus() !== $entityStatusClass::PROCESSED)
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
    protected function getRowVersion(array $row): string
    {
        $version = FundTransferAttempt\Version::V1;

        if (($row[Kotak\Headings::PAYMENT_DETAILS_3] !== null) and
            ($row[Kotak\Headings::PAYMENT_DETAILS_3] === FundTransferAttempt\Version::V3))
        {
            $version = FundTransferAttempt\Version::V3;
        }
        else if (($row[Kotak\Headings::ENRICHMENT_2] !== null) and
            ($row[Kotak\Headings::ENRICHMENT_2] === FundTransferAttempt\Version::V2))
        {
            $version = FundTransferAttempt\Version::V2;
        }

        FundTransferAttempt\Version::validateVersion($version);

        return $version;
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
