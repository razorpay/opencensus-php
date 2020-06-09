<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class NetbankingProcessor extends BaseProcessor
{
    protected $method = Method::NETBANKING;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $unavailableBanks = $this->calculateUnavailableBanks($gatewayDowntimes);

        foreach ($unavailableBanks as $bank)
        {
            $this->createPaymentDowntime($bank, $gatewayDowntimes);
        }

        $this->endOngoingDowntimes($unavailableBanks);
    }

    protected function calculateUnavailableBanks(Collection $gatewayDowntimes)
    {
        if ($gatewayDowntimes->isEmpty() === true)
        {
            return [];
        }

        $mapping = new NetbankingIssuerMapping;

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            $mapping->addDowntime($gatewayDowntime->getGateway(), $gatewayDowntime->getIssuer());
        }

        return $mapping->getUnavailableBanks();
    }

    protected function createPaymentDowntime(string $bank, Collection $gatewayDowntimes)
    {
        $input = $this->getPaymentDowntimeCreationArray($bank, $gatewayDowntimes);

        if ($input === null)
        {
            return;
        }

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            $downtime = (new Core)->edit($downtime, $input);
        }
    }

    protected function getPaymentDowntimeCreationArray(string $bank, Collection $gatewayDowntimes)
    {
        list($begin, $end) = $this->calculateDowntimePeriodForBank($bank, $gatewayDowntimes);

        if ($begin === null)
        {
            return null;
        }

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $status = Status::SCHEDULED;

        if ($scheduled === false)
        {
            $status = Status::STARTED;
        }

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::ISSUER    => $bank,
        ];

        return $input;
    }

    protected function calculateDowntimePeriodForBank(string $bank, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new NetbankingIssuerMapping)->getGatewaysSupportingBank($bank);

        $allGatewayDowntime = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, GatewayDowntime::ALL);

        $allGatewayDowntime = $allGatewayDowntime->sortBy(GatewayDowntime::BEGIN);

        // Filter for issuer and supporting gateways
        // Issuer can be `ALL` if the whole gateway is down or `bank` if only one bank is facing issue
        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways)
                                                      ->whereIn(GatewayDowntime::ISSUER, [GatewayDowntime::ALL, $bank]);

        $begin = $end = null;

        if ($affectingGatewayDowntimes->count() > 0)
        {
            list($begin, $end) = $this->getOverlappingDowntimePeriod($affectingGatewayDowntimes);
        }

        // If all gateways are down for that bank then we don't need
        // to calculate for overlapping gateway downtime
        if ($allGatewayDowntime->count() > 0)
        {
            $allDowntimeBegin = $allGatewayDowntime->first()[GatewayDowntime::BEGIN];

            $allDowntimeEnd = $allGatewayDowntime->first()[GatewayDowntime::END];

            if (($begin === null) or
                ($begin > $allDowntimeBegin))
            {
                return [$allDowntimeBegin, $allDowntimeEnd];
            }
        }

        return [$begin, $end];
    }
}
