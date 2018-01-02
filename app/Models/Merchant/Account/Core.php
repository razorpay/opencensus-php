<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Pricing;
use RZP\Models\Merchant;

class Core extends Merchant\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $parentMerchant
     *
     * @return Entity
     */
    public function createAccount(array $input, Merchant\Entity $parentMerchant) : Entity
    {

        $account = (new Merchant\Core)->createSubMerchant(
                        $input,
                        $parentMerchant,
                        true,
                        true);

        return $account;
    }
}
