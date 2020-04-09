<?php


namespace RZP\Models\BankTransferHistory;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER_HISTORY;
}
