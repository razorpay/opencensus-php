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

    public function getAbsentGatewaysForTimestamp($timestamp = null)
    {
        if ($timestamp === null)
        {
            $timestamp = time();
        }

        return $this->newQuery()
                    ->where(Absence\Entity::FROM, '<=', $timestamp)
                    ->where(Absence\Entity::TO, '>=', $timestamp)
                    ->get();
    }


    public function verifyGatewayExists($gateway)
    {
        return $this->newQuery()
                        ->where(TerminalEntity::GATEWAY, '=', $gateway)
                        ->exists();
    }

}
