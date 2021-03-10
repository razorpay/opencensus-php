<?php

namespace RZP\Models\Partner\Activation;

use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository  extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'partner_activation';

    protected $proxyFetchParamRules = [
        Entity::MERCHANT_ID       => 'sometimes|string|size:14',
    ];
}
