<?php

namespace RZP\Models\Adjustment;

use EE\Exception;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Adjustment';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
    );
}