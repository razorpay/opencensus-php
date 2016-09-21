<?php

namespace RZP\Models\Settlement\Kotak;

use Carbon\Carbon;
use RZP\Exception;
use Excel;
use Mail;
use RZP\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
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
    }

    public function process($input)
    {
        $reconcileFile = $this->getReconcilationFile($input);

        if ($reconcileFile === null)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE, ['message' => 'No file present']);

            return new Base\PublicCollection;
        }

        $data = $this->parseTextFile($reconcileFile);

        if (count($data) === 0)
        {
            return "No rows to process";
        }

        $date = Carbon::createFromFormat('d-M-y', $data[0]['Payment_Date']);

        // update the format so that recon mail is appended to settlement mail
        $date = $date->format('d-m-Y');

        $response = $this->reconcile($data);

        $this->storeReconciledFile($reconcileFile);

        $this->sendReconciliationMail($date, $response);

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
        $utr = null;
        $failureReason = null;
        $type = $entity->getEntity();
        $class = '\\RZP\\Models\\' . ucfirst($type) . '\\Status';

        // get status
        $status = $row['Status Of transaction'];

        if ($status === 'P')
        {
            $status =  $class::PROCESSED;

            $utr = $row['UTR number'];
        }
        else
        {
            $status = $class::FAILED;

            $failureReason = 'Reconciliation: ' . $failureReason;
        }

        // if already processed
        if ($entity->isStatusCreated() === false)
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

            $this->repo->save($entity);

            $entity->transaction->setReconciledAt($this->reconciledAt);
            $this->repo->save($entity->transaction);
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
        });
    }

    protected static function getHeadings()
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
}
