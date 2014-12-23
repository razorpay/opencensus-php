<?php

namespace Models\Settlement;

use Models\Base;
use Models\Gateway;
use Models\Settlement;

class Service extends Base\Service
{
    public function gatewayMprReconcile($input)
    {
        $data = (new MprParser)->process($input);

        $reconciler = new Reconciler;

        $txns = $reconciler->reconcile($data, 'hdfc');

        return $txns->toArrayPublic();
    }

    public function initiateSettlements($input)
    {
        $settler = new Settler();

        $settlements = $settler->settle($input);

        return $settlements->toArrayPublic();
    }

    public function gatewayMprGenerate($input)
    {
        $generator = new MprGenerator($this->mode);

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

    public function returnSettlements($input)
    {
        ;
    }
}
