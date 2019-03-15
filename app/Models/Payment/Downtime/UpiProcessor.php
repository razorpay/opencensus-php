<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class UpiProcessor extends Base\Core
{
    const UPI = 'upi';

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', self::UPI);

        if (($gatewayDowntimes->isEmpty() === true) or
            ($this->impliesUpiDowntime($gatewayDowntimes) === false))
        {
            return;
        }

        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes);

        return $this->createMethodDowntime($begin, $end);
    }

    protected function impliesUpiDowntime(Collection $gatewayDowntimes)
    {
        $gatewaysDown = $gatewayDowntimes->pluck(GatewayDowntime::GATEWAY)->toArray();

        $upiGateways = Gateway::$methodMap[self::UPI];

        if ((in_array(GatewayDowntime::ALL, $gatewaysDown, true) === true) or
            (empty(array_diff($upiGateways, $gatewaysDown)) === true))
        {
            return true;
        }

        return false;
    }

    protected function calculateDowntimePeriod(Collection $gatewayDowntimes)
    {
        $gatewayDowntimeMaxStart = $gatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $gatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }

    protected function createMethodDowntime(int $begin, int $end = null): Entity
    {
        $input = [
            Entity::METHOD => self::UPI,
            Entity::BEGIN  => $begin,
            Entity::END    => $end,
        ];

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            $downtime = (new Core)->edit($downtime, $input);
        }

        return $downtime;
    }

    protected function getDuplicate(array $input)
    {
        return $this->repo->method_downtime->getDuplicate($input);
    }
}
