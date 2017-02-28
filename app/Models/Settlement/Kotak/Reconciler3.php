<?php

namespace RZP\Models\Settlement\Kotak;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Excel;
use Mail;
use RZP\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\BankTransferAttempt;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Constants\MailTags;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Settlement\SlackNotification;

use Illuminate\Support\Facades\App;

class Reconciler3
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    protected static $fileToWriteName = 'Kotak_Settlement_Reconciliation';

    protected static $extraHeadings = array(
        'Status Of transaction',
        'UTR number',
        'Remarks',
        'DateTime',
        'Cms. ref no.',
        'Dummy');

    const SUCCESS_STATUS = [
        'Beneficiary Account Credited',
        'Account Debited',
        'Presented and Paid',
    ];

    const MUTEX_RESOURCE        = 'SETTLEMENT_RECONCILIATION_PROCESSING';

    const MUTEX_LOCK_TIMEOUT    = 300;

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

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

            return new Base\PublicCollection;
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
            $date = Carbon::createFromFormat('d-M-y', $data[0]['Payment_Date']);

            // update the format so that recon mail is appended to settlement mail
            $date = $date->format('d-m-Y');

            $response = $this->reconcile($data);

            $this->storeReconciledFile($reconcileFile);

            $this->sendReconciliationMail($date, $response);
        }

        return $response;
    }

    protected function reconcile($data)
    {
        $collection = new Base\PublicCollection;
        $failures = new Base\PublicCollection;

        $this->repo->settlement->beginTransaction();

        try
        {
            foreach ($data as $row)
            {
                $setl = $this->reconcileSetl($row);

                $collection->push($setl);

                if ($setl->isStatusFailed())
                {
                    $failures->push($setl);
                }
            }

            $this->repo->settlement->commit();
        }
        catch (\Exception $e)
        {
            $this->repo->settlement->rollback();

            (new SlackNotification)->failure('setl_reconciliation', $e);

            throw $e;
        }

        $failureIds = implode(',', $failures->getPublicIds());

        $response = [
            'setl_count'     => $collection->count(),
            'failures_count' => $failures->count(),
            'failure ids'    => $failureIds
        ];

        (new SlackNotification)->success('setl_reconciliation', $response);
        return $response;
    }

    protected function reconcileSetl($row)
    {
        // reconciliation version
        $version = ucfirst($row['Enrichment_2'] ?? 'v1');

        $loadRelations = 'loadSettlementAndRelations' . $version;

        $processSettlementStatus = 'processSettlementStatus' . $version;

        $setl = $this->$loadRelations($row);

        $setl = $this->$processSettlementStatus($setl, $row);

        return $setl;
    }

    protected function loadSettlementAndRelationsV2($row): BankTransferAttempt\Entity
    {
        $bankTransferAttemptId = trim($row['Payment_Ref_No.']);

        $bankTransferAttempt = $this->repo->bank_transfer_attempt->findOrFail($bankTransferAttemptId);

        assert($row['Enrichment_2'] === $bankTransferAttempt->getVersion());

        $setlId = $bankTransferAttempt->getEntityId();

        $setl = $this->repo->settlement->findOrFail($setlId);

        $bankTransferAttempt->sourceAssociate($setl);

        $merchant = $this->repo->merchant->findOrFail($setl->getMerchantId());

        $txn = $this->repo->transaction->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $bankTransferAttempt;
    }

    // get reconciliation data
    protected function processSettlementStatusV2(BankTransferAttempt\Entity $bankTransferAttempt, $row)
    {
        $utr = null;

        $statusCode = $row['Status Of transaction'];

        $recordDate = Carbon::createFromFormat('d-M-y', $row['Payment_Date'], 'Asia/Kolkata');

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $tenPm = $recordDate->hour(22)->timestamp;

        $remarks = $row['Remarks'];

        $failureReason = null;

        if ($statusCode === 'P')
        {
            $utr = trim($row['UTR number']);

            if (empty($utr) === true)
            {
                $utr = null;
            }

            // If current time is before 10 pm, dont mark the settlement as
            // processed and update only the utr
            if ($now < $tenPm)
            {
                $status = Settlement\Status::CREATED;
            }
            else if ((empty($remarks) === true) or
                (in_array($remarks, self::SUCCESS_STATUS) === true))
            {
                $status = Settlement\Status::PROCESSED;
            }
            else
            {
                $status = Settlement\Status::FAILED;

                $failureReason = 'Reconciliation';
            }
        }
        else
        {
            $status = Settlement\Status::FAILED;

            $failureReason = 'Reconciliation';
        }

        $setl = $bankTransferAttempt->source;

        // if already processed
        if ($setl->isStatusCreated() === false)
        {
            $oldStatus = $setl->getStatus();

            if ($oldStatus !== $status)
            {
                $this->trace->warning(
                TraceCode::MISC_TRACE_CODE,
                [
                    'message' => 'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Settlement Id: ' . $setl->getId()
                ]);
                // throw new Exception\BadRequestValidationFailureException(
                //     'Old and new status not matching. ' .
                //     'Old status: ' . $oldStatus . ' New status: ' . $status .
                //     'Settlement Id: ' . $setl->getId());
            }
        }
        // else
        // {
            $bankTransferAttempt->setUtr($utr);
            $bankTransferAttempt->setStatus($status);
            $bankTransferAttempt->setBankStatusCode($statusCode);

            $bankTransferAttempt->setRemarks($remarks);
            $bankTransferAttempt->setFailureReason($failureReason);
            $bankTransferAttempt->setDateTime($row['DateTime']);
            $bankTransferAttempt->setCmsRefNo($row['Cms. ref no.']);

            $this->repo->bank_transfer_attempt->saveOrFail($bankTransferAttempt);

            $setl->setUtr($utr);
            $setl->setStatus($status);
            $setl->setFailureReason($failureReason);
            // $setl->setRemarks($remarks);

            $this->repo->saveOrFail($setl);

            $setl->transaction->setReconciledAt($this->reconciledAt);
            $this->repo->saveOrFail($setl->transaction);
        // }

        return $setl;
    }

    // get reconciliation data
    protected function processSettlementStatusV1(Settlement\Entity $setl, $row): Settlement\Entity
    {
        $utr = null;

        $status = $row['Status Of transaction'];

        $remarks = substr($row['Remarks'], 0, 255);

        $recordDate = Carbon::createFromFormat('d-M-y', $row['Payment_Date'], 'Asia/Kolkata');

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $tenPm = $recordDate->hour(22)->timestamp;

        $failureReason = null;

        if ($status === 'P')
        {
            $utr = trim($row['UTR number']);

            if (empty($utr) === true)
            {
                $utr = null;
            }

            // If current time is before 10 pm, dont mark the settlement as
            // processed and update only the utr
            if ($now < $tenPm)
            {
                $status = Settlement\Status::CREATED;
            }
            else if ((empty($remarks) === true) or
                (in_array($remarks, self::SUCCESS_STATUS) === true))
            {
                $status = Settlement\Status::PROCESSED;
            }
            else
            {
                $status = Settlement\Status::FAILED;

                $failureReason = 'Reconciliation';
            }
        }
        else
        {
            $status = Settlement\Status::FAILED;

            $failureReason = 'Reconciliation';
        }

        // if already processed
        if ($setl->isStatusCreated() === false)
        {
            $oldStatus = $setl->getStatus();

            if ($oldStatus !== $status)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Settlement Id: ' . $setl->getId());
            }
        }
        else
        {
            $setl->setUtr($utr);

            $setl->setStatus($status);

            $setl->setFailureReason($failureReason);

            $setl->setRemarks($remarks);

            $this->repo->saveOrFail($setl);

            $setl->transaction->setReconciledAt($this->reconciledAt);
            $this->repo->saveOrFail($setl->transaction);
        }

        return $setl;
    }

    protected function loadSettlementAndRelationsV1($row): Settlement\Entity
    {
        $setlId = $row['Payment_Ref_No.'];
        $setlId = str_replace(' ', '_', $setlId);

        if ($setlId === '')
        {
            // Check if row is empty.
            if (strlen(implode($row)) === 0)
            {
                return;
            }
        }

        Settlement\Entity::verifyIdAndStripSign($setlId);

        $setl = $this->repo->settlement->findOrFail($setlId);

        $merchant = $this->repo->merchant->findOrFail($setl->getMerchantId());

        $txn = $this->repo->transaction->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
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
        $headings = Kotak\NodalAccount::getHeadings();

        $headings = array_merge($headings, static::$extraHeadings);

        return $headings;
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

    protected function parseTextRowWithHeadingMismatch($headings, $values, $ix)
    {
        $count = count($values);

        assert(($count === 54) or ($count === 55));

        $headings = array_slice($headings, 0, $count);

        $values = array_combine($headings, $values);

        return $values;
    }
}
