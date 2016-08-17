<?php

namespace RZP\Models\GatewayStatus\Absence;

use RZP\Models\Base;
use RZP\Models\GatewayStatus\Absence;
use RZP\Models\Payment\Gateway;
use RZP\Exception;

class Service extends Base\Service
{
    public function create(array $input, $gateway)
    {
        $status = Gateway::isValidGateway($gateway);

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

        $downWindow = (new Absence\Core)->edit($downWindow, $input);

        return $downWindow->toArrayPublic();

    }

    public function delete($id)
    {
        try
        {
            $downWindow = $this->repo->gateway_absence->findOrFailPublic($id);

            $this->repo->gateway_absence->delete($downWindow);

            return ['message' => 'Gateway Absence successfully deleted'];
        }
        catch(\Exception $e)
        {
            throw $e;
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

}
