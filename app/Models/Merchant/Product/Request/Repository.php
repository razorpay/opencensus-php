<?php

namespace RZP\Models\Merchant\Product\Request;

use RZP\Models\Base\Repository as BaseRepository;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends BaseRepository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_product_request';

}
