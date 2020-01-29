<?php

namespace RZP\Models\Merchant\LegalEntity;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function upsert(Merchant\Entity $merchant, array $input): Entity
    {
        (new Validator)->validateInput('edit', $input);

        $legalEntity = $merchant->legalEntity;

        if (empty($legalEntity) === false)
        {
            $legalEntity = $this->edit($legalEntity, $input);
        }
        else
        {
            $legalEntity = $this->create($input);
        }

        return $legalEntity;
    }

    protected function create(array $input)
    {
        $legalEntity = (new Entity)->generateId();

        $legalEntity->build($input);

        $this->repo->saveOrFail($legalEntity);

        return $legalEntity;
    }

    protected function edit(Entity $legalEntity, array $input)
    {
        $legalEntity->edit($input);

        $this->repo->saveOrFail($legalEntity);

        return $legalEntity;
    }
}
