<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime\ReasonCode;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class WalletProcessor extends BaseProcessor
{
    protected $method = Method::WALLET;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $unavailableWallets = [];

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            $this->createPaymentDowntime($gatewayDowntime);

            $unavailableWallets[] = Gateway::getWalletForGateway($gatewayDowntime->getGateway());
        }

        $this->endOngoingDowntimes($unavailableWallets);
    }

    protected function createPaymentDowntime(GatewayDowntime $gatewayDowntime): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($gatewayDowntime);

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

    protected function getPaymentDowntimeCreationArray(GatewayDowntime $gatewayDowntime): array
    {
        $issuer = Gateway::getWalletForGateway($gatewayDowntime->getGateway());

        $severity = ReasonCode::getSeverity($gatewayDowntime->getReasonCode());

        $status = Status::SCHEDULED;

        if($gatewayDowntime->isScheduled() === false)
        {
            $status = Status::STARTED;
        }

        $input = [
            Entity::METHOD    => $this->method,
            Entity::BEGIN     => $gatewayDowntime->getBegin(),
            Entity::END       => $gatewayDowntime->getEnd(),
            Entity::STATUS    => $status,
            Entity::SCHEDULED => $gatewayDowntime->isScheduled(),
            Entity::SEVERITY  => $severity,
            Entity::ISSUER    => $issuer,
        ];

        return $input;
    }
}
