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
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Payout;
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
        'Reject Reason',
        'DateTime',
        'Int.ref no.',
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

        $this->repo->beginTransaction();

        try
        {
            foreach ($data as $row)
            {
                $entity = $this->reconcileEntity($row);

                $collection->push($entity);

                if ($entity->isStatusFailed())
                {
                    $failures->push($entity);
                }
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

        $response = [
            'total_count'    => $collection->count(),
            'failures_count' => $failures->count(),
            'failure ids'    => $failureIds
        ];

        (new SlackNotification)->success('setl_reconciliation', $response);
        return $response;
    }

    protected function reconcileEntity($row)
    {
        $entity = $this->loadEntityAndRelations($row);

        $entity = $this->processEntityStatus($entity, $row);

        return $entity;
    }

    protected function processEntityStatus($entity, $row)
    {
        // get reconciliation data
        $utr = null;

        $type = $entity->getEntity();

        $class = '\\RZP\\Models\\' . ucfirst($type) . '\\Status';

        $status = $row['Status Of transaction'];

        $remarks = substr($row['Reject Reason'], 0, 255);

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
        else
        {
            $status = $class::FAILED;

            $failureReason = 'Reconciliation';
        }

        // if already processed
        if ($entity->isPendingReconciliation() === false)
        {
            $oldStatus = $entity->getStatus();

            if ($oldStatus !== $status)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Old and new status not matching. ' .
                    'Old status: ' . $oldStatus . ' New status: ' . $status .
                    'Settlement Id: ' . $entity->getId());
            }
        }
        else
        {
            $entity->setUtr($utr);

            $entity->setStatus($status);

            $entity->setFailureReason($failureReason);

            $entity->setRemarks($remarks);

            $this->repo->saveOrFail($entity);

            $entity->transaction->setReconciledAt($this->reconciledAt);

            $this->repo->saveOrFail($entity->transaction);
        }

        return $entity;
    }

    protected function loadEntityAndRelations($row)
    {
        $entityId = $row['Payment_Ref_No.'];

        $entityId = str_replace(' ', '_', $entityId);

        if ($entityId === '')
        {
            // Check if row is empty.
            if (strlen(implode($row)) === 0)
            {
                return;
            }
        }

        $entity = $this->getEntityById($entityId);

        $merchantId = $entity->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $txn = $this->repo->transaction->findOrFail($entity->getTransactionId());

        $entity->merchant()->associate($merchant);

        $entity->transaction()->associate($txn);

        return $entity;
    }

    protected function getEntityById($entityId)
    {
        $entity = null;

        if (strpos($entityId, Settlement\Entity::getSign(), 0) === 0)
        {
            Settlement\Entity::verifyIdAndStripSign($entityId);

            $entity = $this->repo->settlement->findOrFail($entityId);
        }
        else if(strpos($entityId, Payout\Entity::getSign(), 0) === 0)
        {
            Payout\Entity::verifyIdAndStripSign($entityId);

            $entity = $this->repo->payout->findOrFail($entityId);
        }

        return $entity;
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
