<?php

namespace Models\Settlement\Kotak;

use Models\Settlement\Kotak;
use Models\Base;

class Service extends Base\Service
{
    public function reconcileSettlements($input)
    {
        $collection = (new Kotak\Reconciler)->process($input);

        return $collection->toArrayPublic();
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
}