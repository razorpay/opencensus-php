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
use RZP\Models\Adjustment;
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
        $setl = $this->loadSettlementAndRelations($row);

        $setl = $this->processSettlementStatus($setl, $row);

        return $setl;
    }

    protected function processSettlementStatus($setl, $row)
    {
        // get reconciliation data
        $utr = null;

        $status = $row['Status Of transaction'];

        $failureReason = $row['Reject Reason'];

        if ($status === 'P')
        {
            $utr = $row['UTR number'];

            if (empty($failureReason) === true)
            {
                $status = Settlement\Status::PROCESSED;

                $failureReason = null;
            }
            else
            {
                $status = Settlement\Status::FAILED;

                $failureReason = 'Reconciliation: ' . $failureReason;
            }
        }
        else
        {
            $status = Settlement\Status::FAILED;

            $failureReason = 'Reconciliation: ' . $failureReason;
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
            if ($status === Settlement\Status::FAILED)
            {
                $failureHandler = new Failurehandler($setl);

                $failureHandler->markFailed($failureReason);
            }
            else
            {
                $setl->setUtr($utr);
                $setl->setStatus($status);
                $setl->setFailureReason($failureReason);

                $this->repo->settlement->save($setl);
            }

            $setl->transaction->setReconciledAt($this->reconciledAt);
            $this->repo->transaction->save($setl->transaction);
        }

        return $setl;
    }

    protected function loadSettlementAndRelations($row)
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

        $merchantId = $setl->getMerchantId();

        $merchant = $this->repo->merchant->findOrFail($merchantId);

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
}
