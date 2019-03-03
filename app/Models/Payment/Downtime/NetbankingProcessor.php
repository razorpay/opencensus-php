<?php

namespace RZP\Models\Gateway\MethodDowntime;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class NetbankingProcessor extends Base\Core
{
    const NETBANKING = 'netbanking';

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', self::NETBANKING);

        if ($gatewayDowntimes->isEmpty() === true)
        {
            return;
        }

        $unavailableBanks = $this->calculateUnavailableBanks($gatewayDowntimes);

        if (empty($unavailableBanks) === true)
        {
            return;
        }

        foreach ($unavailableBanks as $bank)
        {
            list($begin, $end) = $this->calculateDowntimePeriodForBank($bank, $gatewayDowntimes);

            $this->createMethodDowntime($begin, $end);
        }
    }

    protected function calculateUnavailableBanks(Collection $gatewayDowntimes)
    {
        $mapping = new NetbankingIssuerMapping;

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            $mapping->addDowntime($gatewayDowntime->getGateway(), $gatewayDowntime->getIssuer());
        }

        return $mapping->getUnavailableBanks();
    }

    protected function calculateDowntimePeriodForBank(string $bank, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new NetbankingIssuerMapping)->getGatewaysSupportingBank($bank);

        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways);

        $gatewayDowntimeMaxStart = $affectingGatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $affectingGatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }

    protected function createMethodDowntime(int $begin, int $end = null): Entity
    {
        $input = [
            Entity::METHOD => self::NETBANKING,
            Entity::BEGIN  => $begin,
            Entity::END    => $end,
            // TODO:
            // Add instrument fields here
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
