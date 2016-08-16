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
        $repo = $this->repo;

        $results =  $repo->where(Entity::GATEWAY, '=', $gateway->getId());

        return $results->get();
    }


    public function deleteAbsence($id)
    {
        $repo = $this->repo;

        try
        {
            $downWindow = $repo::where(Entity::ID, '=', $id)
                ->firstOrFail();

            return $downWindow->delete();

        }
        catch(\Exception $e)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Terminal Absence cannot be deleted:'.$e->getMessage());
        }
    }

    public function getAbsentGatewaysForTimestamp($timestamp = null)
    {
        if($timestamp === null)
        {
            $timestamp = time();
        }

        return (new Absence\Entity)->newQuery()
            ->where(Absence\Entity::FROM, '<=', $timestamp)
            ->where(Absence\Entity::TO, '>=', $timestamp)
            ->get();
    }


    public function verifyGatewayExists($gateway)
    {
        $count = $this->newQuery()
            ->where(TerminalEntity::GATEWAY, '=', $gateway)
            ->count();
        if($count === 0)
        {
            return false;
        }

        return true;
    }
}
