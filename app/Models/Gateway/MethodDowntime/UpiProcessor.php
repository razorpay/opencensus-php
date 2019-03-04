<?php

namespace RZP\Models\Gateway\MethodDowntime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;
use RZP\Models\Payment\Gateway;

class UpiProcessor extends Base\Core
{
    const UPI = 'upi';

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', self::UPI);

        if (($gatewayDowntimes->isEmpty() === true) and
            ($this->impliesUpiDowntime($gatewayDowntimes) === false))
        {
            return;
        }

        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes);

        $this->createMethodDowntime($begin, $end);
    }

    protected function impliesUpiDowntime(Collection $gatewayDowntimes)
    {
        $gatewaysDown = $gatewayDowntimes->pluck(GatewayDowntime::Gateway);

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

    protected function createMethodDowntime(int $begin, int $end): Entity
    {
        $input = [
            Entity::METHOD => self::UPI,
            Entity::BEGIN  => $begin,
            Entity::END    => $end,
        ];

        (new Core)->create($input);
    }
}
