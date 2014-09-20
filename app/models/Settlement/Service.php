<?php

namespace Models\Settlement;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Gateway;
use Models\Ledger;

class Service extends Base\Service
{
    public function gatewayMprReconcile($input)
    {
        $data = MprParser::parseMprFile($input['mpr']);

        $reconciler = new Reconciler($data, $input['gateway']);

        return $reconciler->reconcile($data, $input['gateway']);
    }

    public function getLedgerRecords($input)
    {
        $lgrs = (new Ledger\Repository)->fetch($input);

        return $lgrs->toPublicArray();
    }

    public function getLedgerRecordById($id)
    {
        $lgr = (new Ledger\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $lgr->toArrayPublic();
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
