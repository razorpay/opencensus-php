<?php

namespace RZP\Models\Payment\Downtime;

use Illuminate\Database\Eloquent\Collection;

use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Payment\Method;
use RZP\Models\Gateway\Downtime\Source;
use RZP\Gateway\Upi\Base\ProviderPsp;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;

class UpiProcessor extends BaseProcessor
{
    protected $method = Method::UPI;

    public function process(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::METHOD, '=', $this->method);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_UPI, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        $platformDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', null);

        $merchantDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null);

        $this->processPlatform($platformDowntime);

        $this->processMerchant($merchantDowntime);
    }

    protected function processMerchant(Collection $gatewayDowntimes)
    {
        $merchantIds = $gatewayDowntimes->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();

        foreach ($merchantIds as $merchantId)
        {
            $merchantDowntimes = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', $merchantId);

            $this->processPlatform($merchantDowntimes, $merchantId);
        }

        $this->endOngoingDowntimesForMerchants($merchantIds);
    }

    protected function processPlatform(Collection $gatewayDowntimes, $mid=null)
    {
        $vpaList = $this->getUnavailableVpaList($gatewayDowntimes);

        foreach ($vpaList as $vpa)
        {
            if($vpa === GatewayDowntime::ALL)
            {
                $downtimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', null);
            }
            else
            {
                $downtimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', $vpa);
            }

            $this->createPaymentDowntime($downtimes, $vpa);
        }

        $this->endOngoingDowntimes($vpaList, $mid);

        $this->googlePayDowntime($gatewayDowntimes, $mid);
    }

    protected function impliesUpiDowntime(Collection $gatewayDowntimes)
    {
        $gatewaysDown = $gatewayDowntimes->pluck(GatewayDowntime::GATEWAY)->toArray();

        // We are checking the gateways that are being actively used.
        $upiGateways = Constants::UPI_GATEWAYS;

        if (in_array(GatewayDowntime::ALL, $gatewaysDown, true) === true)
        {
            return true;
        }

        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes);

        // The time check exists because there could be two non-overlapping
        // but mutually exhaustive downtimes in the future
        if ((empty(array_diff($upiGateways, $gatewaysDown)) === true) and
            ((($end === null) or
             ($begin < $end))))
        {
            return true;
        }

        return false;
    }

    protected function createPaymentDowntime(Collection $gatewayDowntimes, $vpa = null, $psp = null): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($gatewayDowntimes, $vpa, $psp);

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
        }
        else
        {
            // During edit the status gets updated and hence multiple notifications are triggered.
            if (isset($input[Entity::SCHEDULED]) && isset($input[Entity::SEVERITY]))
            {
                $updateList = [
                    Entity::SEVERITY => $input[Entity::SEVERITY],
                    Entity::SCHEDULED => $input[Entity::SCHEDULED],
                ];
                $downtime = (new Core)->edit($downtime, $updateList);
            }
        }

        return $downtime;
    }

    protected function getPaymentDowntimeCreationArray(Collection $gatewayDowntimes, $vpa = null, $psp = null): array
    {
        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes, $vpa);

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $status = Status::SCHEDULED;

        if ($scheduled === false)
        {
            $status = Status::STARTED;
        }

        $input = [
            Entity::METHOD      => $this->method,
            Entity::BEGIN       => $begin,
            Entity::END         => $end,
            Entity::STATUS      => $status,
            Entity::SCHEDULED   => $scheduled,
            Entity::SEVERITY    => $severity,
            Entity::VPA_HANDLE  => $vpa,
            Entity::PSP         => $psp ?? UpiVpaMapping::getPsp($vpa),
        ];

        $mids = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null)->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();

        if(sizeof($mids) === 1)
        {
            $input[Entity::MERCHANT_ID] = $mids[0];
        }

        return $input;
    }

    protected function calculateDowntimePeriod(Collection $gatewayDowntimes, $vpa = null): array
    {
        if( isset($vpa) === true && $vpa != GatewayDowntime::ALL)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', $vpa);
        }

        $gatewayDowntimeMaxStart = $gatewayDowntimes->max(GatewayDowntime::BEGIN);

        $gatewayDowntimeMinEnd = $gatewayDowntimes->filter(function ($downtime) {
            return ($downtime->getEnd() !== null);
        })->min(GatewayDowntime::END);

        return [$gatewayDowntimeMaxStart, $gatewayDowntimeMinEnd];
    }

    protected function getUnavailableVpaList(Collection $gatewayDowntimes): array
    {
        $gatewaydowntimes = $gatewayDowntimes->unique(GatewayDowntime::VPA_HANDLE);

        $gatewaydowntimes = $gatewaydowntimes->where(GatewayDowntime::VPA_HANDLE, '!=', null);

        $vpa = $gatewaydowntimes->pluck(GatewayDowntime::VPA_HANDLE)->toArray();

        $gatewayDown = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', null);

        if ($this->impliesUpiDowntime($gatewayDown) === true)
        {
            array_push($vpa, GatewayDowntime::ALL);
        }

        return $vpa;
    }

    protected function googlePayDowntime($gatewayDowntimes, string $mid=null)
    {
        $activeDowntime = $this->getRepo()->fetchOngoingDowntimesByMethodAndMerchant($this->method, $mid);

        $activeDowntime = $activeDowntime->where(Entity::PSP, '=', ProviderPsp::GOOGLE_PAY);

        $gatewayDowntimes = $gatewayDowntimes
            ->whereIn(Entity::VPA_HANDLE, [ProviderCode::OKAXIS,ProviderCode::OKHDFCBANK, ProviderCode::OKICICI, ProviderCode::OKSBI]);

        if ($activeDowntime->isEmpty() === true)
        {
            if ($this->isGooglePayDown($gatewayDowntimes) === true)
            {
                $this->createPaymentDowntime($gatewayDowntimes, null, ProviderPsp::GOOGLE_PAY);
            }
        }
        else
        {
            if ( $this->isGooglePayDown($gatewayDowntimes) === false)
            {
                $this->endDowntime($activeDowntime);
            }
        }
    }

    protected function isGooglePayDown($gatewayDowntimes)
    {
        $vpaMap = UpiVpaMapping::getMultiplePspVpaMapping();

        foreach ($gatewayDowntimes as $gatewayDowntime)
        {
            $vpa = $gatewayDowntime->getVpaHandle();

            array_delete($vpa, $vpaMap[ProviderPsp::GOOGLE_PAY]);
        }

        if (empty( $vpaMap[ProviderPsp::GOOGLE_PAY]))
        {
            return true;
        }

        return false;
    }
}
