<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Models\Gateway\Downtime\ReasonCode;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class WalletProcessor extends BaseProcessor
{
    protected $method = Method::WALLET;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_WALLET, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        $unavailableWallets = [];

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {

            if($this->shouldUseMutex()) {
                $input = $this->getPaymentDowntimeCreationArray($gatewayDowntime);

                $this->createPaymentDowntimeWithMutex($input);
            }
            else
            {
                $this->createPaymentDowntime($gatewayDowntime);
            }

            $unavailableWallets[] = Gateway::getWalletForGateway($gatewayDowntime->getGateway());
        }

        $this->endOngoingDowntimes($unavailableWallets);
    }

    protected function createPaymentDowntimeWithMutex(array $input)
    {
        $mutexKey = $input[Entity::METHOD] . $input[Entity::ISSUER] . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

        $this->mutex->acquireAndRelease(
            $mutexKey,
            function () use ($input)
            {
                $this->createPaymentDowntimeWithDowntimeCreationArray($input);
            },
            10,
            ErrorCode::BAD_REQUEST_PAYMENT_DOWNTIME_MUTEX_TIMED_OUT
        );
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
            // During update the status gets updated and hence multiple notifications are triggered.
            if (isset($input[Entity::STATUS]))
            {
                unset($input[Entity::STATUS]);
            }

            $downtime = (new Core)->edit($downtime, $input);
        }

        return $downtime;
    }

    protected function createPaymentDowntimeWithDowntimeCreationArray(array $input): Entity
    {
        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            if (isset($input[Entity::STATUS]))
            {
                unset($input[Entity::STATUS]);
            }

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
