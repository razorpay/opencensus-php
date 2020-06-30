<?php


namespace RZP\Models\BankTransferRequest;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER_REQUEST;

    public function updateByUtr(string $utr, array $data)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, $utr)
                    ->update($data);
    }
}
