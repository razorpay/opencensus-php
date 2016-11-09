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

class Reconciler2
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected static $headings = array(
        'CLIENT CODE',
        'UPLOAD DATE',
        'ACCOUNT NO',
        'MY PRODUCT CODE',
        'BANK PRODUCT',
        'DEBIT DATE',
        'AMOUNT',
        'BENEF ACCOUNT NUMBER',
        'BENEF NAME',
        'UTR NO',
        'PAYABLE BRANCH',
        'INSTRUMENT NO',
        'REFERENCE NUMBER',
        'STATUS',
        'PDTIFSCCODE');

    public function __construct()
    {
        $this->reconciledAt = time();

        $this->merchantRepo = new Merchant\Repository;
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->dailySetlRepo = new Settlement\Daily\Repository;
        $this->trace = \App::make('trace');
    }

    public function process($input)
    {
        $reconcileFile = $this->getFile($input);

        if ($reconcileFile === null)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE, ['message' => 'No file present']);

            return new Base\PublicCollection;
        }

//        $this->dailySettlement = $this->dailySetlRepo->getSettlementForTodayOrFail('kotak');

        $url = $this->saveUploadedFileToAws($reconcileFile);

//        $this->dailySettlement->addUrl('kotak_reconcile_txt', $url);

        $data = $this->parseReturnFile($reconcileFile);

//        $this->dailySettlement->addUrl('kotak_reconcile_excel', $url);

        //
        // In excel, dates are displayed properly, but in reality, are stored as
        // integer value. The integer value is the number of days from 1/1/1990
        // which has the value of 1.
        //
        // We actually need to subtract 2 from the integer value to reach
        // the correct date. (Sad, but true!)
        //

        $daysSince1900 = (int) $data[0]['debit_date'];
        $date = Carbon::createFromDate(1900, 1, 1)->addDays($daysSince1900 - 2);
        $date = $date->format('d-m-Y');

        list($settlements, $failures) = $this->reconcile($data);

        $this->storeReconciledFile($reconcileFile);

        $this->sendReconciliationMail($date, $failures);

        return $settlements;
    }

    protected function parseReturnFile($file)
    {
        $data = Excel::selectSheetsByIndex(0)
                     ->load($file)
                     ->formatDates(false)
                     ->toArray();

        if ((count($data) === 3) and
            (count($data[1]) === 0))
        {
            //
            // For excel files, with 3 sheets, we get the
            // data for first sheet only, discarding other sheets.
            // The simple check to determine sheets is that they will 3
            // in number and data in second sheet should be empty.
            //
            $data = $data[0];
        }

        $rows = count($data);

        for ($ix = $rows - 1; $ix > 0; $ix--)
        {
            if (strlen(implode($data[$ix])) === 0)
            {
                unset($data[$ix]);
            }
            else
            {
                break;
            }
        }

        return $data;
    }

    protected function reconcile($data)
    {
        $collection = new Base\PublicCollection;
        $failures = new Base\PublicCollection;

        $this->setlRepo->beginTransaction();

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

//            $this->dailySettlement->reconciled_at = $this->reconciledAt;
//            $this->dailySettlement->saveOrFail();

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->failure('setl_reconciliation', $e);

            throw $e;
        }

        $failureIds = implode(',', $failures->getPublicIds());

        $slackData = [
            'setl_count'     => $collection->count(),
            'failures_count' => $failures->count(),
            'failure ids'    => $failureIds];

        (new SlackNotification)->success('setl_reconciliation', $slackData);

        return [$collection, $failures];
    }

    protected function reconcileSetl($row)
    {
        if (isset($row['utr_sr_no']))
        {
            $row['utr_no'] = $row['utr_sr_no'];
        }

        $setl = $this->loadSettlementAndRelations($row);

        $setl = $this->processSettlementStatus($setl, $row);

        return $setl;
    }

    protected function processSettlementStatus($setl, $row)
    {
        $status = $row['status'];

        if ($setl->isStatusCreated() === false)
        {
            // throw new Exception\BadRequestValidationFailureException(
            //     'Settlement status should be created for reconciliation. ' .
            //     'Current status: ' . $setl->getStatus());
        }

        if (isset($row['utr_no']))
        {
            $utr = $row['utr_no'];
            $utr = ($utr === '') ? null : $utr;
            $setl->setUtr($utr);
        }
        else
        {
            assert ($row['pdtifsccode'] === '958');
        }

        if (($status === 'Account Debited') or
            ($status === 'Presented and Paid'))
        {
            $status = Settlement\Status::PROCESSED;
        }
        else
        {
            $status = Settlement\Status::FAILED;
        }

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

                $failureHandler->markFailed();
            }
            else
            {
                $setl->setStatus($status);
                $this->setlRepo->save($setl);
            }
            $setl->transaction->setReconciledAt($this->reconciledAt);
            $this->txnRepo->save($setl->transaction);
        }

        return $setl;
    }

    protected function loadSettlementAndRelations($row)
    {
        $setlId = $row['reference_number'];
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

        $setl = $this->setlRepo->findOrFail($setlId);

        $merchantId = $setl->getMerchantId();

        $merchant = $this->merchantRepo->findOrFail($merchantId);

        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }

    protected function sendReconciliationMail($date, $failures)
    {
        $msg = 'UTR File reconciled.' . PHP_EOL;
        $failureCount = $failures->count();
        $msg .= 'Failure Count: ' . $failureCount . PHP_EOL;

        if ($failureCount !== 0)
        {
            $msg .= 'Failed settlement ids: ' . implode(',', $failures->getPublicIds());
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

    protected function getFileToReadName()
    {
        return $this->getExcelFileToReadName();
    }
}
