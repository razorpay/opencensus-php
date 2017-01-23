<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Service extends Merchant\Service
{
    public function fetch($id)
    {
        $merchant = $this->repo->account->findByPublicIdAndMerchant($id, $this->merchant);

        return $merchant->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        // $input[Entity::PARENT_ID] = $this->merchant->getId();

        $merchants = $this->repo->account->fetch($input, $this->merchant->getId());

        return $merchants->toArrayPublic();
    }
}
