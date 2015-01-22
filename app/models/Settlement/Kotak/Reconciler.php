<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;
use Models\Settlement\SlackNotification;

class Reconciler
{
    use FileHandlerTrait;

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
        $this->setlRepo = new \Models\Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
    }

    public function process($input)
    {
        $reconcileFile = $input['setlReconciliationFile'];

        $data = $this->parseTextFile($reconcileFile);

        return $this->reconcile($data);
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
                // Trace this
                // @todo: Raise this issue with Kotak bank to get the actual reason
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

    protected static function getHeadings()
    {
        $headings = Kotak\NodalAccount::getHeadings();

        $headings = array_merge($headings, static::$extraHeadings);

        return $headings;
    }
}