<?php

namespace RZP\Models\FundTransfer\Kotak;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function reconcileSettlements($input)
    {
        $collection = (new Reconciler)->process($input);

        return $collection->toArray();
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
        $urls = (new NodalAccount)->generateSettlementFile($setlAttempts, false);

        return $urls;
    }

    public function deleteSetlFile($setlFileType)
    {
        (new FileDeleter)->deleteFileIfExists($setlFileType);
    }
}
