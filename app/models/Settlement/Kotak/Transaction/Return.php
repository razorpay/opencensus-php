<?php

namespace Models\Settlement\Kotak;

use EE\Exception;
use Excel;
use Models\Base;
use Models\Merchant;
use Models\Transaction;
use Models\Settlement;
use Models\Settlement\Kotak;
use Models\Settlement\SlackNotification;

class ReturnTransactions
{
    use Kotak\FileHandlerTrait;

    protected static $fileToReadName = 'Kotak_Return_Transaction';

    protected static $headings = array(
        'BATCHTIME',
        'TXN REF NO',
        'SND BRN IFSC',
        'ACCT TYP1',
        'SEND CUST ACNO',
        'SEND CUST ACNAME',
        'BENF IFSC',
        'BENE CUST ACTYP',
        'BENE CUST ACNO',
        'BENE CUST ACNAME',
        'RETURN UTR NO1',
        'REMITT INFO',
        'AMOUNT',
        'RETURN REASON',
        'APAC',
        'TXN DATE',
        'VIRTUAL APC');

    public function __construct()
    {
        $this->merchantRepo = new Merchant\Repository;
        $this->setlRepo = new Settlement\Repository;
        $this->txnRepo = new Transaction\Repository;
        $this->dailySetlRepo = new Settlement\Daily\Repository;
    }

    public function process($input)
    {
        $returnFile = $this->getFile($input);

        if ($returnFile === null)
            return [];

        $url = $this->saveUploadedFileToAws($returnFile);

        $this->dailySettlement = $this->dailySetlRepo->getSettlementForToday();

        $this->dailySettlement->addUrl('kotak_return_txt', $url);

        $data = $this->parseTextFile($returnFile);

        $urlExcel = $this->writeToExcelFile($data, $this->getFileToReadNameWithoutExt());

        $this->dailySettlement->addUrl('kotak_return_excel', $urlExcel);

        $data = $this->processReturns($data);

        $this->storeReconciledFile($returnFile);

        return $data;
    }

    protected function processReturns($rows)
    {
        $this->setlRepo->beginTransaction();

        try
        {
            $collection = $this->reconcileReturns($rows);

            $this->setlRepo->commit();
        }
        catch (\Exception $e)
        {
            $this->setlRepo->rollback();

            (new SlackNotification)->queueOperationFailure('setl_return', $e);

            throw $e;
        }

        $slackData = [
            'setl_failures' => $collection->count()];

        (new SlackNotification)->queueOperationSuccess('setl_return', $slackData);

        return $collection;
    }

    protected function reconcileReturns($rows)
    {
        $collection = new Base\PublicCollection;

        foreach ($rows as $row)
        {
            $setl = $this->loadSettlementAndRelations($row);

            $setl = $this->processSettlementFailure($setl, $row);

            $collection->push($setl);
        }

        $this->dailySettlement->returned_at = time();
        $this->dailySettlement->saveOrFail();

        return $collection;
    }

    protected function processSettlementFailure($setl, $row)
    {
        $setl->setStatus(Settlement\Status::FAILED);

        $failureReason = null;

        if ($row['RETURN UTR NO1'] !== null)
        {
            $returnUtr = $row['RETURN UTR NO1'];
            $setl->setAttribute(Settlement\Entity::RETURN_UTR, $returnUtr);
            $failureReason = 'Return reason: ' . $row['RETURN REASON'];
        }
        else
        {
            $failureReason = 'Remitt info: ' . $row['REMITT INFO'];
        }

        (new Settlement\Failure)->markFailed($setl, $failureReason);
    }

    protected function loadSettlementAndRelations($row)
    {
        $setlId = $row['TXN REF NO'];
        Settlement\Entity::verifyIdAndStripSign($setlId);
        $setl = $this->setlRepo->findOrFail($setlId);

        $txn = $this->txnRepo->findOrFail($setl->getTransactionId());
        $merchant = $this->merchantRepo->findOrFail($setl->getMerchantId());

        $setl->merchant()->associate($merchant);
        $setl->transaction()->associate($txn);

        return $setl;
    }

    protected function getReturnFile($input)
    {
        if (isset($input['setlReturnFile']))
        {
            return $input['setlReturnFile'];
        }

        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $path = storage_path('files/settlement');

        $name = 'Kotak_Return_Transaction';

        $fullpath = $path . '/' . $name.'_'.$time.'.txt';

        if (file_exists($fullpath) === false)
        {
            // @todo: trace here
            return null;
        }

        return $fullpath;
    }
}