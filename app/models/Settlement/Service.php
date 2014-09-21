<?php

namespace Models\Settlement;

use Models\Base;
use Models\Gateway;
use Models\Ledger;

class Service extends Base\Service
{
    public function gatewayMprReconcile($input)
    {
        $data = MprParser::parseMprFile($input['mpr']);

        $reconciler = new Reconciler($data, $input['gateway']);

        $lgrs = $reconciler->reconcile($data, $input['gateway']);

        return $lgrs->toArrayPublic();
    }

    public function generateSettlements()
    {
        $settler = new Settler();

        $settler->settle();

        return $setlements->toArray();
    }

    public function gatewayMprGenerate()
    {
        $generator = new MprGenerator($this->mode);

        return $generator->generateTestMprForToday();
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
}
