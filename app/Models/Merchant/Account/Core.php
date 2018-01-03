<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
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
