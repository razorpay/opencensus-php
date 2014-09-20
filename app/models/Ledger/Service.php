<?php

namespace Models\Service;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Gateway;
use Models\Ledger;

class Service extends Base\Service
{
    public function getLedgerRecords($input)
    {
        $lgrs = (new Ledger\Repository)->fetch($input);

        return $lgrs->toPublicArray();
    }

    public function getLedgerRecordById($id)
    {
        Ledger\Entity::verifyIdAndStripSign($id);

        $lgr = (new Ledger\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $lgr->toArrayPublic();
    }
}