<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'banking_account';

    public function findByBankReferenceAndChannel(string $channel, string $bankReference = null)
    {
        return $this->newQuery()
                    ->where(Entity::BANK_REFERENCE_NUMBER, '=', $bankReference)
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->firstOrFail();
    }

    public function getBankingAccountOfMerchant(Merchant\Entity $merchant, string $channel)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->where(Entity::CHANNEL, '=', $channel)
                    ->first();
    }
}
