<?php

namespace Models\Settlement;

use Constants\Mode;
use Models\Base;
use Models\Gateway;
use Models\Transaction;
use Models\Settlement;

class Service extends Base\Service
{
    public function gatewayMprReconcile($input)
    {
        $reconciler = new Mpr\Reconciler;

        $txns = $reconciler->process($input);

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

    public function fetch($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = (new Settlement\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $settlements = (new Settlement\Repository)->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }

    public function getSettlementTransactions($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = (new Settlement\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        $txns = (new Transaction\Repository)->fetchBySettlementId($id);

        return $txns->toArrayPublic();
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
        if ($setlFileType === 'hdfc_mpr')
            return Gateway::call(\Models\Payment\Gateway::HDFC, 'deleteMprFileIfExists', null, Mode::TEST);

        return (new Kotak\Service)->deleteSetlFile($setlFileType);
    }
}
