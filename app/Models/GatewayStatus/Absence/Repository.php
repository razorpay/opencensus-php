<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;


class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'gateway_absence';
    
    public function findForGateway($gateway)
    {
        $repo = $this->repo;

        $results =  $repo->where(Entity::GATEWAY, '=', $gateway->getId());

        return $results->get();
    }

}
