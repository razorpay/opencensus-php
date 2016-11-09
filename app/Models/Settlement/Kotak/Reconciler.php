<?php

namespace RZP\Models\Settlement\Kotak;

use RZP\Exception;
use RZP\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Transaction;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Settlement\SlackNotification;

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
        {
            return new Base\PublicCollection;
        }

        $this->dailySettlement = $this->dailySetlRepo->getSettlementForTodayOrFail('kotak');

        $url = $this->saveUploadedFileToAws($reconcileFile);

        $this->dailySettlement->addUrl('kotak_reconcile_txt', $url);

        $data = $this->parseTextFile($reconcileFile);

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToReadNameWithoutExt());

        $this->dailySettlement->addUrl('kotak_reconcile_excel', $url);

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
        $status = $row['Success'];

        $utr = $row['UTR'];
        $utr = ($utr === '') ? null : $utr;

        $setl->setUtr($utr);

        $failureReason = $row['Failure Reason'];

        if ($status === 'P')
        {
            $setl->setStatus(Settlement\Status::PROCESSED);
            $this->setlRepo->save($setl);
        }
        else
        {
            if ($failureReason !== '')
            {
                $failureReason = 'Reconciliation: ' . $failureReason;
            }

            $failureHandler = new Failurehandler($setl);

            $failureHandler->markFailed($failureReason);

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

        $merchantId = $row['Payment Details 2'];
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

    public static function getHeadings()
    {
        $headings = Kotak\NodalAccount::getHeadings();

        $headings = array_merge($headings, static::$extraHeadings);

        return $headings;
    }
}
