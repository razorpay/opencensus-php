<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FundAccount\Type as Type;

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

        $this->callFTSCreateAccount($vpa);

        return $vpa;
    }

    protected function callFTSCreateAccount($vpa)
    {
        $id = $vpa->getId();

        FTSCreateAccount::dispatch($id, $this->mode, Type::VPA);
    }
}
