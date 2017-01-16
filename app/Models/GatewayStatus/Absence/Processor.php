<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\GatewayStatus\Absence;
use RZP\Trace\TraceCode;
use App;
use RZP\Models\Base;

class Processor extends Base\Core
{
    protected $uniqueCheckerKeys = [
        Entity::GATEWAY,
        Entity::ISSUER,
        Entity::METHOD,
        Entity::DOWNTIME_FROM
    ];

    public function createAction(array $input)
    {
        //
        // Prevent duplicate creation of the same error model.
        // Basically, since we pass an empty 'to', it means, this is for an unscheduled
        // maintenance. In case of a scheduled maintenance, the 'to' param is set
        // and this will return null. For an unscheduled one, in case there already
        // does exist a record for the same gateway, issuer and method, do not create
        // additional ones.
        //
        $alreadyPresent = $this->verifyIfExists($input);

        if (empty($alreadyPresent) === false)
        {
            return $alreadyPresent->toArrayPublic();
        }

        $downWindow = (new Absence\Core)->create($input);

        return $downWindow->toArrayPublic();
    }

    public function editAction(string $id, array $input)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $downWindow = (new Absence\Core)->edit($downWindow, $input);

        return $downWindow->toArrayPublic();
    }

    public function deleteAction(string $id)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        $this->repo->gateway_absence->deleteOrFail($downWindow);

        $this->trace->info(TraceCode::GATEWAY_ABSENCE_DELETE, ['id' => $id]);

        return $downWindow->toArrayDeleted();
   }

    public function verifyIfExists(array $input)
    {
        $queryParams = [];

        foreach ($this->uniqueCheckerKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $queryParams[$key] = $input[$key];
            }
        }

        $absentees = $this->repo->gateway_absence->fetch($queryParams);

        return $absentees->first();
    }

    public function fetchMostRecentActive(array $input)
    {
        $queryParams = [];

        foreach ($this->uniqueCheckerKeys as $key)
        {
            if (isset($input[$key]) === true)
            {
                $queryParams[$key] = $input[$key];
            }
        }

        $activeAbsentees = $this->repo->gateway_absence->fetchMostRecentActive($queryParams);

        return $activeAbsentees->first();
    }
}
