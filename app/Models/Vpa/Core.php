<?php

namespace RZP\Models\Vpa;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createForSource(array $input, Base\PublicEntity $source = null, bool $compositePayoutSaveOrFail = true, Merchant\Entity $merchantEntity = null): Entity
    {
        $vpa = (new Entity)->build($input);

        /** @var Merchant\Entity $merchant */
        if ($source !== null)
        {
            $merchant = $source->merchant;
        }
        else
        {
            $merchant = $merchantEntity;
        }

        $vpa->merchant()->associate($merchant);

        $vpa->source()->associate($source);

        if ($compositePayoutSaveOrFail === true)
        {
            $this->repo->saveOrFail($vpa);
        }

        return $vpa;
    }

    public function getVpaEntity($id)
    {
        return $this->repo->vpa->find($id);
    }

//    public function updateVpaWithFtsId(Entity $entity, $ftsFundAccountId)
//    {
//        $entity->setFtsFundAccountId($ftsFundAccountId);
//
//        $this->repo->saveOrFail($entity);
//    }
}
