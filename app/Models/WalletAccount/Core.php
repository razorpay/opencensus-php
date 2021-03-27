<?php

namespace RZP\Models\WalletAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createForSource(array $input, Base\PublicEntity $source): Entity
    {
        $wallet = (new Entity)->build($input);

        /** @var Merchant\Entity $merchant */
        $merchant = $source->merchant;

        $wallet->merchant()->associate($merchant);

        $wallet->source()->associate($source);

        $this->repo->saveOrFail($wallet);

        return $wallet;
    }

    public function getWalletEntity($id)
    {
        return $this->repo->wallet->findOrFail($id);
    }
}
