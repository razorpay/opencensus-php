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
    
    public function findForGateway($gateway)
    {
        $results = $this->newQuery()
                        ->where(Entity::GATEWAY, '=', $gateway->getId());

        return $results->get();
    }

    public function getAbsentGatewaysForTimestamp($timestamp)
    {
        return $this->newQuery()
                    ->where(Absence\Entity::FROM, '<=', $timestamp)
                    ->where(Absence\Entity::TO, '>=', $timestamp)
                    ->get();
    }

}
