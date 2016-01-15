<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Excel;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;
use Models\Settlement\SlackNotification;
use Trace;
use Trace\TraceCode;

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
    }

    public function process($input)
    {
        $reconcileFile = $this->getFile($input);

        if ($reconcileFile === null)
        {
            return new Base\PublicCollection;
        }

//        $this->dailySettlement = $this->dailySetlRepo->getSettlementForTodayOrFail('kotak');

        $url = $this->saveUploadedFileToAws($reconcileFile);

//        $this->dailySettlement->addUrl('kotak_reconcile_txt', $url);

        $data = $this->parseReturnFile($reconcileFile);

        // $this->storeReconciledFile($mprFile);
        $urlExcel = $this->writeToExcelFile($data, $this->getFileToReadNameWithoutExt());

//        $this->dailySettlement->addUrl('kotak_reconcile_excel', $url);

        $data = $this->reconcile($data);

        $this->storeReconciledFile($reconcileFile);

        return $data;
    }

    protected function parseReturnFile($file)
    {
        $data = Excel::load($file)
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

        return $data;
    }

    protected function reconcile($data)
    {
        $collection = new Base\PublicCollection;

        $this->setlRepo->beginTransaction();

        try
        {
            foreach ($data as $row)
            {
                $setl = $this->reconcileSetl($row);

                $collection->push($setl);
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

        $slackData = [
            'setl_count' => $setl->count()];

        (new SlackNotification)->success('setl_reconciliation', $slackData);

        return $collection;
    }

    protected function reconcileSetl($row)
    {
        $setl = $this->loadSettlementAndRelations($row);

        $setl = $this->processSettlementStatus($setl, $row);

        return $setl;
    }

    protected function processSettlementStatus($setl, $row)
    {
        $status = $row['status'];

        $utr = $row['utr_no'];
        $utr = ($utr === '') ? null : $utr;

        $setl->setUtr($utr);

        if (($status === 'Account Debited') or
            ($status === 'Presented and Paid'))
        {
            $setl->setStatus(Settlement\Status::TRANSFERRED);
            $this->setlRepo->save($setl);
        }
        else
        {
            $failureReason = $row['Failure_Reason'];

            throw new Exception\BadRequestValidationFailureException(
                'Something going wrong. ' . $status);

            if ($failureReason !== '')
            {
                $failureReason = 'Reconciliation: ' . $failureReason;
            }

            (new Failure)->markFailed($setl, $reason);

            if (($status !== 'C') or
                ($failureReason === ''))
            {
                Trace::error(TraceCode::SETTLEMENT_KOTAK_FAILURE_DATA_MISSING);
            }
        }

        $setl->transaction->setReconciledAt($this->reconciledAt);
        $this->txnRepo->save($setl->transaction);

        return $setl;
    }

    protected function loadSettlementAndRelations($row)
    {
        $setlId = $row['reference_number'];
        $setlId = str_replace(' ', '_', $setlId);

        Settlement\Entity::verifyIdAndStripSign($setlId);

        $setl = $this->setlRepo->findOrFail($setlId);

        $merchantId = $setl->getMerchantId();

        $merchant = $this->merchantRepo->findOrFail($merchantId);

        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }

    protected function getSetlReconciliationFile($input)
    {
        // if (isset($input['setlReconciliationFile']))
        // {
        //     return $input['setlReconciliationFile']->;
        // }

        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $path = storage_path('files/settlement');

        $name = 'Kotak_Settlement_Reconciliation';

        $fullpath = $path . '/' . $name.'_'.$time.'.txt';

        if (file_exists($fullpath) === false)
        {
            // @todo: trace here
            return null;
        }

        return $fullpath;
    }

    protected function getFileToReadName()
    {
        return $this->getExcelFileToReadName();
    }
}
