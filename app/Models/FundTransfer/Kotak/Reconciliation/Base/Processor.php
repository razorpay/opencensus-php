<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use Carbon\Carbon;
use Excel;
use Mail;

use RZP\Constants\Entity as EntityConstants;
use RZP\Constants\MailTags;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Attempt as FundTransferAttempt;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Settlement\SlackNotification;
use RZP\Trace\TraceCode;

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

    protected $firstFailureEntities = [];

    protected $dashboardUrl;

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

        $this->dashboardUrl = $this->app['config']->get('applications.dashboard.url');
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

            $this->sendReconciliationEmails($response);
        }

        return $response;
    }

    protected function startReconciliation($data): array
    {
        $unprocessedIds = [];

        $this->repo->beginTransaction();

        try
        {
            foreach ($data as $row)
            {
                $reconResponse = $this->reconcileEntity($row);

                $entity = $reconResponse['entity'];

                $this->updateBatchFundTransferStats($entity);

                if ($reconResponse['first_failure'] === true)
                {
                    $this->firstFailureEntities[$entity->getMerchantId()] = $entity;
                }

                if ($entity === null)
                {
                    $this->unprocessedIds[] = $row[Kotak\Headings::PAYMENT_REF_NO] ?? 'null';
                }
                else
                {
                    $this->allEntities[] = $entity;
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

            $this->repo->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->rollback();

            (new SlackNotification)->failure('setl_reconciliation', $e);

            throw $e;
        }

        $summary = $this->getSummary();

        (new SlackNotification)->success('setl_reconciliation', $summary);

        return $summary;
    }

    protected function reconcileEntity($row)
    {
        $version = $this->getSettlementVersion($row);

        $versionRowProcessorClass = 'RZP\\Models\\FundTransfer\\Kotak\\Reconciliation\\' . ucwords($version) . '\\RowProcessor';

        $reconResponse = (new $versionRowProcessorClass($row))->process($this->reconciledAt);

        return $reconResponse;
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

        foreach ($this->allEntities as $entity)
        {
            $entityId = $entity->getId();

            if ($entity->isStatusFailed())
            {
                $failureEntityIds[] = $entityId;
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
        $failureEntityIds = array_unique($failureEntityIds);

        // If multiple, let's say 2, attempts were made, on the same day for a settlement,
        // the recon file would have both failure and success rows corresponding to each
        // attempt. In this case the settlement corresponding to them would be part of
        // both successEntities, and failureEntities. To avoid a false alarm for this
        // settlement, we do this
        $failureEntityIds = array_diff($failureEntityIds, $successEntityIds);

        return [
            'total_count'               => count($allEntityIds),
            'failures_count'            => count($failureEntityIds),
            'failure ids'               => implode(',', $failureEntityIds),
            'processing failed ids'     => implode(',', $this->unprocessedIds),
        ];
    }

    protected function sendReconciliationEmails($response)
    {
        $this->sendReconciliationSummaryMail($response);

        if (empty($this->firstFailureEntities) !== true)
        {
            $this->sendReconciliationFailureEmails();
        }
    }

    protected function sendReconciliationFailureEmails()
    {
        $data = [];

        foreach ($this->firstFailureEntities as $merchantId => $entity)
        {
            $data['merchant_id'] = $merchantId;

            $data['remarks'] = $entity->getRemarks();

            $data['profile_link'] = $this->dashboardUrl . '#/app/profile';

            // bankAccount for Settlelemt entity, and destination for Payout entity
            $ba = $entity->destination ?? $entity->bankAccount;
            $data['last4'] = $ba->getRedactedAccountNumber();

            $data['merchant_email'] = $entity->merchant->getEmail();

            $data['subject'] = 'Razorpay | Notification for failed settlement on your account ' . $merchantId;

            Mail::queue('emails.merchant.settlement_failure', $data, function($message) use ($data)
            {
                $emails = $data['merchant_email'];
                // $emails = 'priyanshu.chhazed@razorpay.com';

                $message->from('care@razorpay.com', 'Razorpay Settlement Support');
                // $message->from('priyanshu.chhazed@razorpay.com', 'Razorpay Settlement Support');

                $message->cc('support@razorpay.com');

                $message->subject($data['subject']);

                $message->to($emails);

                $headers = $message->getHeaders();

                $headers->addTextHeader(MailTags::HEADER, MailTags::SETTLEMENT_FAILURE_EMAIL);
            });
        }
    }

    protected function sendReconciliationSummaryMail($response)
    {
        $msg = 'UTR File reconciled.' . PHP_EOL;

        $failureCount = $response['failures_count'];

        $msg .= 'Failure Count: ' . $failureCount . PHP_EOL;

        if ($failureCount !== 0)
        {
            $msg .= 'Failed settlement ids: ' . $response['failure ids'];
        }

        $data['subject'] = "Re: Kotak Settlement files for $this->date";
        $data['date'] = $this->date;
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
