<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\Terminal\Entity as TerminalEntity;
use RZP\Exception;
use RZP\Models\GatewayStatus\Absence;


class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'gateway_absence';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::BANK           => 'sometimes|string|max:255'
    );

    // These are proxy allowed params to search on.
    protected $appFetchParamRules = array(
        Entity::GATEWAY        => 'sometimes|string|max:255',
        Entity::BANK           => 'sometimes|string|max:255',
        Entity::FROM           => 'sometimes|integer',
        Entity::TO             => 'sometimes|integer'
    );

}
