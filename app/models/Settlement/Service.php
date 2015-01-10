<?php

namespace Models\Settlement;

use Models\Base;
use Models\Gateway;
use Models\Settlement;

class Service extends Base\Service
{
    public function gatewayMprReconcile($input)
    {
        $data = (new Mpr\Parser)->process($input);

        $reconciler = new Mpr\Reconciler;

        $txns = $reconciler->reconcile($data, 'hdfc');

        return $txns->toArrayPublic();
    }

    public function initiateSettlements($input)
    {
        $settler = new Settler();

        $settlementFile = $settler->settle($input);

        return ['setlFile' => $settlementFile];
    }

    public function gatewayMprGenerate($input)
    {
        $generator = new Mpr\Generator($this->mode);

        return $generator->generateTestMpr($input);
    }

    public function getSettlement($id)
    {
        $setl = (new Settlement\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $setl->toArrayPublic();
    }

    public function getSettlements($input)
    {
        $settlements = (new Settlement\Repository)->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }

    public function reconcileSettlements($input)
    {
        $data = (new Kotak\Reconciler)->process($input);

        return $data;
    }

    public function generateSettlementReconciliation($input)
    {
        $filename = (new Kotak\ReconciliationGenerator)->generateReconcileFile($input);

        return ['setlReconciliationFile' => $filename];
    }

    public function returnSettlements($input)
    {
        ;
    }
}
