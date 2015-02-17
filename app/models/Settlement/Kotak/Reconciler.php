<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;
use Models\Settlement\SlackNotification;
use Trace;
use Trace\TraceCode;

class Reconciler
{
    use FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Settlement_Reconciliation';

    /**
     * All payments in the current mpr
     * will have the same reconciledAt timestamp
     * @var int
     */
    protected $reconciledAt;

    protected static $extraHeadings = array(
        'Success',
        'UTR',
        'Failure Reason',
        'Date');

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
            return new Base\PublicCollection;

        $this->dailySettlement = $this->dailySetlRepo->getSettlementForToday();

        $url = $this->saveUploadedFileToAws($reconcileFile);

        $this->dailySettlement->addUrl('reconcile_url', $url);

        $data = $this->parseTextFile($reconcileFile);

        $data = $this->reconcile($data);

        $this->storeReconciledFile($reconcileFile);

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

            $this->dailySettlement->reconciled_at = $this->reconciledAt;
            $this->dailySettlement->saveOrFail();

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->queueOperationFailure('setl_reconciliation', $e);

            throw $e;
        }

        $slackData = [
            'setl_count' => $setl->count()];

        (new SlackNotification)->queueOperationSuccess('setl_reconciliation', $slackData);

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
        $status = $row['Success'];

        $utr = $row['UTR'];
        $utr = ($utr === '') ? null : $utr;

        $setl->setUtr($utr);

        $failureReason = $row['Failure Reason'];

        if ($status === 'P')
        {
            $setl->setStatus(Settlement\Status::TRANSFERRED);
            $this->setlRepo->save($setl);
        }
        else
        {
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
        $setlId = $row['Payment_Ref_No.'];
        Settlement\Entity::verifyIdAndStripSign($setlId);
        $setl = $this->setlRepo->findOrFail($setlId);

        $merchantId = $row['Payment Details 1'];
        $merchant = $this->merchantRepo->findOrFail($merchantId);

        if ($merchantId !== $setl->getMerchantId())
        {
            throw new Exception\LogicException(
                'Merchant id must match. ' . $merchantId . ' ' . $setlId->getMerchantId());
        }

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

    protected static function getHeadings()
    {
        $headings = Kotak\NodalAccount::getHeadings();

        $headings = array_merge($headings, static::$extraHeadings);

        return $headings;
    }
}