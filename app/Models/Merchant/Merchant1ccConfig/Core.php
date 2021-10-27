<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function createAndSaveConfig(Merchant\Entity $merchant, $input)
    {
        $input[Entity::MERCHANT_ID] = $merchant->getId();

        $config = (new Entity)->build($input);
        $config->generateId();
        $this->repo->merchant_1cc_configs->saveOrFail($config);

        return $config;
    }
}
