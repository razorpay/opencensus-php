<?php

namespace Models\Merchant;

use Models\Base
use Models\Merchant;
use Models\Pricing;

class Core extends Base\Core;
{
    public function create($input)
    {
        $merchant = (new Merchant\Entity)->build($input);

        return $merchant;
    }

    public function createAndSave($input)
    {
        $entity = $this->create($input);

        (new Repository)->saveOrFail($entity);

        return $entity;
    }
}
