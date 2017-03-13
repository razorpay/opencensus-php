<?php

namespace RZP\Models\FundTransfer\Kotak;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function reconcileSettlements($input)
    {
        //
        // We have incorporated the new format we are receiving for reconciliation
        // in Reconciler2 class, while also keeping the old one around in Reconciler.
        // On testing, we use Reconciler, which let the tests pass basically.
        // @todo: Write tests for the newer format
        //

        $collection = (new Reconciler2)->process($input);

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
