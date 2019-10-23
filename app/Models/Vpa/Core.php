<?php

namespace RZP\Models\Vpa;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createForSource(array $input, Base\PublicEntity $source): Entity
    {
        $vpa = (new Entity)->build($input);

        /** @var Merchant\Entity $merchant */
        $merchant = $source->merchant;

        $vpa->merchant()->associate($merchant);

        $vpa->entity()->associate($source);

        $this->repo->saveOrFail($vpa);

        return $vpa;
    }

    public function getVpaEntity($id)
    {
        return $this->repo->vpa->findOrFailPublic($id);
    }

    public function updateVpaWithFtsId(Entity $entity, $ftsFundAccountId)
    {
        $entity->setFtsFundAccountId($ftsFundAccountId);

        $this->repo->saveOrFail($entity);
    }
}
