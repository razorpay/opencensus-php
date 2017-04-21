<?php

namespace RZP\Models\FundTransfer\Kotak;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function reconcileSettlements($input)
    {
        $data = (new Reconciler)->process($input);

        return $data;
    }

    public function reconcileH2HSettlements($input)
    {
        $data = (new Reconciler)->process($input);

        return $data;
    }

    public function generateSettlementReconciliation($input)
    {
        $filename = (new ReconciliationGenerator)->generateReconcileFile($input);

        return ['setlReconciliationFile' => $filename];
    }

    public function returnSettlements($input)
    {
        $data = (new ReturnTransactions)->process($input);

        return $data;
    }

    public function generateSettlementReturn($input)
    {
        $filename = (new ReturnTransactionsGenerator)->generate($input);

        return ['setlReturnFile' => $filename];
    }

    public function generateSettlementFile($setlAttempts)
    {
        $fileDetails = (new NodalAccount)->generateSettlementFile($setlAttempts, false);

        $urls = [];

        foreach ($fileDetails as $fileDetail)
        {
            $urls[($fileDetail->get())['id']] = $fileDetail->getUrl();
        }

        return $urls;
    }

    public function deleteSetlFile($setlFileType)
    {
        (new FileDeleter)->deleteFileIfExists($setlFileType);
    }
}
