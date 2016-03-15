<?php

namespace Models\Order;

use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'order';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::STATUS 			=> 'sometimes|in:created,attempted,paid',
        Entity::AUTHORIZED 		=> 'sometimes|in:0,1',
    );

    protected $entityFetchParamRules = array(
        Entity::AUTHORIZED      => 'sometimes|in:0,1',
    );
}
