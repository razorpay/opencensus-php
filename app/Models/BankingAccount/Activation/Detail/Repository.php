<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Base;
use Carbon\Carbon;
use RZP\Constants\Table;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_activation_detail';

    public function getFromBankingAccountId(string $bankingAccountId): Entity
    {
        return $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccountId)
                    ->firstOrFail();
    }
}
