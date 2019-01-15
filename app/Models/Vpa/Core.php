<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createForBankingSource(array $input, Base\PublicEntity $source): Entity
    {
        $vpa = (new Entity)->build($input);

        /** @var Merchant\Entity $merchant */
        $merchant = $source->merchant;

        $vpa->merchant()->associate($merchant);

        $vpa->entity()->associate($source);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }
}
