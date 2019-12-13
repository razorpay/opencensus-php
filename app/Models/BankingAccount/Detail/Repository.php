<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\BankingAccount;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account_detail';

    public function getDetailsForKeyAndBankingAccount(BankingAccount\Entity $bankingAccount, string $key)
    {
        $details = $this->newQuery()
                    ->where(Entity::BANKING_ACCOUNT_ID, '=', $bankingAccount->getId())
                    ->where(Entity::GATEWAY_KEY, '=', $key)
                    ->get();

        if (count($details) > 1)
        {
            throw new Exception\LogicException(
                'banking_account_detail has more than 1 entry for a gateway_key',
                null,
                [
                    Entity::BANKING_ACCOUNT_ID  => $bankingAccount->getId(),
                    Entity::GATEWAY_KEY         => $key,
                    'count'                     => count($details),
                ]);
        }

        return $details->first();
    }
}
