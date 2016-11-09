<?php

namespace RZP\Models\Settlement\Kotak;

use RZP\Models\Settlement\Kotak;
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

        $collection = (new Kotak\Reconciler2)->process($input);

        return $collection->toArray();
    }

    public function reconcileH2HSettlements($input)
    {
        $data = (new Kotak\Reconciler3)->process($input);

        return $data;
    }

    public function generateSettlementReconciliation($input)
    {
        $filename = (new Kotak\ReconciliationGenerator)->generateReconcileFile($input);

        return ['setlReconciliationFile' => $filename];
    }

    public function returnSettlements($input)
    {
        $data = (new Kotak\ReturnTransactions)->process($input);

        return $data;
    }

    public function generateSettlementReturn($input)
    {
        $filename = (new Kotak\ReturnTransactionsGenerator)->generate($input);

        return ['setlReturnFile' => $filename];
    }

    public function generateSettlementFile($setls)
    {
        $urls = (new Kotak\NodalAccount)->generateSettlementFile($setls, false);

        return $urls;
    }

    public function deleteSetlFile($setlFileType)
    {
        (new FileDeleter)->deleteFileIfExists($setlFileType);
    }
}
