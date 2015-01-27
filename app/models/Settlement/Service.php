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

    public function initiateSettlements($input, $channel = null)
    {
        $settler = new Settler();

        return $settler->settle($input, $channel);
    }

    public function gatewayMprGenerate($input)
    {
        $generator = new Mpr\Generator($this->mode);

        return $generator->generateTestMpr($input);
    }

    public function getSettlement($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

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
        return (new Kotak\Service)->reconcileSettlements($input);
    }

    public function generateSettlementReconciliation($input)
    {
        return (new Kotak\Service)->generateSettlementReconciliation($input);
    }

    public function returnSettlements($input)
    {
        return (new Kotak\Service)->returnSettlements($input);
    }

    public function generateSettlementReturn($input)
    {
        return (new Kotak\Service)->generateSettlementReturn($input);
    }

    public function deleteSetlFile($setlFileType)
    {
        return (new Kotak\Service)->deleteSetlFile($setlFileType);
    }
}
