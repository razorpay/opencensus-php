<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::LISTING_ID  => 'sometimes|string',
    ];
}