<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;
use RZP\Exception;

class Service extends Base\Service
{
    public function create(array $input, $gateway)
    {
        $status = $this->repo->gateway_absence->verifyGatewayExists($gateway);
        
        if($status === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Gateway ['.$gateway.'] does not exist');
        }

        $downWindow = (new Absence\Core)->create($input, $gateway);

        return $downWindow->toArrayPublic();

    }

    public function edit($id, array $input)
    {
        $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

        SD($input);

        $downWindow = (new Absence\Core)->edit($downWindow, $input);

        return $downWindow->toArrayPublic();

    }

    public function delete($id)
    {
        $flag = $this->repo->gateway_absence->deleteAbsence($id);

        if($flag === true)
        {
            return ['message' => 'Gateway Absence successfully deleted'];
        }
    }

    public function findAbsentGatewaysForTimestamp($timestamp)
    {
        $gateways = $this->repo->gateway_absence->getAbsentGatewaysForTimestamp($timestamp);

        return $gateways->toArrayPublic();
    }

    public function getScheduleForGateway($gateway)
    {
        $schedule = $this->repo->gateway_absence->findForGateway($gateway);

        return $schedule->toArrayPublic();
    }

    public function getSchedulesBetween($from, $to)
    {
        $schedule = $this->repo->gateway_absence->findBetweenTimestampsForGateway($from, $to);

        return $schedule->toArrayPublic();
    }
}
