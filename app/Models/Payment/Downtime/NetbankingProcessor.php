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

        if (empty($unavailableBanks) === true)
        {
            $this->endOngoingDowntimes();
        }
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

    protected function createPaymentDowntime(string $bank, Collection $gatewayDowntimes): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($bank, $gatewayDowntimes);

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

    protected function getPaymentDowntimeCreationArray(string $bank, Collection $gatewayDowntimes): array
    {
        list($begin, $end) = $this->calculateDowntimePeriodForBank($bank, $gatewayDowntimes);

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $begin,
            Entity::END       => $end,
            Entity::STATUS    => Status::SCHEDULED,
            Entity::SCHEDULED => $scheduled,
            Entity::SEVERITY  => $severity,
            Entity::ISSUER    => $bank,
        ];

        return $input;
    }

    protected function calculateDowntimePeriodForBank(string $bank, Collection $gatewayDowntimes)
    {
        $supportingGateways = (new NetbankingIssuerMapping)->getGatewaysSupportingBank($bank);

        // Gateway downtime can also be created as gateway = ALL which
        // gets skipped while filtering affectingGatewayDowntimes
        $supportingGateways = array_merge($supportingGateways, [GatewayDowntime::ALL]);

        $affectingGatewayDowntimes = $gatewayDowntimes->whereIn(GatewayDowntime::GATEWAY, $supportingGateways);

        $gatewayDowntimeMaxStart = $affectingGatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $affectingGatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }
}
