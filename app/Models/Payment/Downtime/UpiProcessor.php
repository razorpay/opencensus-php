<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;
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

        $this->trace->info(TraceCode::FILTERED_METHOD_PROC_SPECIFICS, ["context"=>$gatewayDowntimes]);

        $paymentDowntimesEnabled = (bool) ConfigKey::get(ConfigKey::ENABLE_PAYMENT_DOWNTIME_UPI, false);

        if ($paymentDowntimesEnabled === false)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::SOURCE, '!=', Source::DOWNTIME_V2);
        }

        $platformDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', null);

        $merchantDowntime = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null);

        $this->trace->info(TraceCode::PLATFORM_SPECIFIC_DOWNTIMES, ["context"=>$platformDowntime]);
        $this->trace->info(TraceCode::MERCHANT_SPECIFIC_DOWNTIMES, ["context"=>$merchantDowntime]);

        $this->processPlatform($platformDowntime);

        $this->processMerchant($merchantDowntime);
    }

    protected function processMerchant(Collection $gatewayDowntimes)
    {
        $merchantIds = $gatewayDowntimes->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();

        foreach ($merchantIds as $merchantId)
        {
            $merchantDowntimes = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '=', $merchantId);
            $this->trace->info(TraceCode::MERCHANT_DOWNTIME_CREATION, ["merchantId" =>$merchantId, "downtimes"  => $merchantDowntimes]);
            $this->processPlatform($merchantDowntimes, $merchantId);
        }

        $this->endOngoingDowntimesForMerchants($merchantIds);
    }

    protected function processPlatform(Collection $gatewayDowntimes, $mid=null)
    {
        $vpaList = $this->getUnavailableVpaList($gatewayDowntimes);

        $unavailableIssuers = $this->getUnavailableIssuers($gatewayDowntimes);

        foreach ($vpaList as $vpa)
        {
            if($vpa === GatewayDowntime::ALL)
            {
                $downtimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', null);

                $downtimes = $downtimes->whereIn(GatewayDowntime::ISSUER, [GatewayDowntime::UNKNOWN, GatewayDowntime::NA, null]);
            }
            else
            {
                $downtimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', $vpa);
            }

            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME, ["vpa"=>$vpa, "downtime"=>$downtimes]);

            if($this->shouldUseMutex()) {
                $input = $this->getPaymentDowntimeCreationArray($downtimes, $vpa, 'vpa');

                $mutexKey = $input[Entity::METHOD] . $vpa . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

                $this->createDowntimeWithMutex($input, $mutexKey);
            }
            else
            {
                $this->createPaymentDowntime($downtimes, $vpa, 'vpa');
            }
        }

        foreach ($unavailableIssuers as $unavailableIssuer)
        {
            $downtimes = $gatewayDowntimes->where(GatewayDowntime::ISSUER, '=', $unavailableIssuer);
            $this->trace->info(TraceCode::CREATE_UNAVAILABLE_BANK_DOWNTIME_NXT, ["downtime"=>$downtimes]);

            if($this->shouldUseMutex()) {
                $input = $this->getPaymentDowntimeCreationArray($downtimes, $unavailableIssuer, 'issuer');

                $mutexKey = $input[Entity::METHOD] . $unavailableIssuer . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

                $this->createDowntimeWithMutex($input, $mutexKey);
            }
            else
            {
                $this->createPaymentDowntime($downtimes, $unavailableIssuer, 'issuer');
            }
        }

        $unavailableList = array_merge($vpaList, $unavailableIssuers);

        $this->endOngoingDowntimes($unavailableList, $mid);

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

    protected function createPaymentDowntime(Collection $gatewayDowntimes, $instrument = null, $instrumentType = null): Entity
    {
        $input = $this->getPaymentDowntimeCreationArray($gatewayDowntimes, $instrument, $instrumentType);

        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
            $this->trace->info(TraceCode::CREATE_NEW_PAYMENT_DOWNTIME, ["downtime" =>$downtime]);
        }
        else
        {
            // During edit the status gets updated and hence multiple notifications are triggered.
            if (isset($input[Entity::SCHEDULED]) && isset($input[Entity::SEVERITY]) &&
                ($input[Entity::SEVERITY] != $downtime->getSeverity()))
            {
                $updateList = [
                    Entity::SEVERITY => $input[Entity::SEVERITY],
                    Entity::SCHEDULED => $input[Entity::SCHEDULED],
                ];
                $downtime = (new Core)->edit($downtime, $updateList);
                $this->trace->info(TraceCode::EDIT_PAYMENT_DOWNTIME, ["downtime" =>$downtime]);
            }
        }

        return $downtime;
    }

    protected function createPaymentDowntimeWithDowntimeCreationArray(array $input): Entity
    {
        $downtime = $this->getDuplicate($input);

        if ($downtime === null)
        {
            $downtime = (new Core)->create($input);
            $this->trace->info(TraceCode::CREATE_NEW_PAYMENT_DOWNTIME, ["downtime" => $downtime]);
        }
        else {
            if (isset($input[Entity::SCHEDULED]) && isset($input[Entity::SEVERITY]) &&
                ($input[Entity::SEVERITY] != $downtime->getSeverity())) {
                $updateList = [
                    Entity::SEVERITY => $input[Entity::SEVERITY],
                    Entity::SCHEDULED => $input[Entity::SCHEDULED],
                ];
                $downtime = (new Core)->edit($downtime, $updateList);
                $this->trace->info(TraceCode::EDIT_PAYMENT_DOWNTIME, ["downtime" => $downtime]);
            }
            else {
                $this->trace->info(TraceCode::BAD_REQUEST_PAYMENT_DOWNTIME_DUPLICATE_EDIT, ["downtime" =>$downtime]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_DOWNTIME_DUPLICATE_EDIT);
            }
        }

        return $downtime;

    }

    protected function getPaymentDowntimeCreationArray(Collection $gatewayDowntimes, $instrument = null, $instrumentType = null): array
    {
        list($begin, $end) = $this->calculateDowntimePeriod($gatewayDowntimes, $instrument, $instrumentType);

        $scheduled = $this->calculateDowntimeScheduled($gatewayDowntimes);

        $severity = $this->calculateDowntimeSeverity($gatewayDowntimes);

        $status = Status::SCHEDULED;

        if ($scheduled === false)
        {
            $status = Status::STARTED;
        }

        $flow = $gatewayDowntimes->pluck(GatewayDowntime::CARD_TYPE)[0];

        $input = [
            Entity::METHOD      => $this->method,
            Entity::BEGIN       => $begin,
            Entity::END         => $end,
            Entity::STATUS      => $status,
            Entity::SCHEDULED   => $scheduled,
            Entity::SEVERITY    => $severity,
            Entity::TYPE        => $flow,
        ];

        switch ($instrumentType)
        {
            case 'vpa':
                $input[Entity::VPA_HANDLE] = $instrument;
                $input[Entity::PSP] = UpiVpaMapping::getPsp($instrument);
                break;
            case 'psp':
                $input[Entity::PSP] = $instrument;
                break;
            case 'issuer':
                $input[Entity::ISSUER] = $instrument;
                break;
        }

        $mids = $gatewayDowntimes->where(GatewayDowntime::MERCHANT_ID, '!=', null)->unique(GatewayDowntime::MERCHANT_ID)->pluck(GatewayDowntime::MERCHANT_ID)->toArray();

        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        if(sizeof($mids) === 1)
        {
            $input[Entity::MERCHANT_ID] = $mids[0];
        }

        $this->trace->info(TraceCode::FINAL_DOWNTIME_OBJECT, ["downtimeObject" => $input]);

        return $input;
    }

    protected function calculateDowntimePeriod(Collection $gatewayDowntimes, $instrument = null, $instrumentType = null): array
    {
        if( $instrumentType == 'vpa' && $instrument != GatewayDowntime::ALL)
        {
            $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::VPA_HANDLE, '=', $instrument);
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

        $gatewayDown = $gatewayDown->whereIn(GatewayDowntime::ISSUER, [GatewayDowntime::UNKNOWN, GatewayDowntime::NA, null]);

        if ($this->impliesUpiDowntime($gatewayDown) === true)
        {
            array_push($vpa, GatewayDowntime::ALL);
        }

        return $vpa;
    }

    protected function getUnavailableIssuers(Collection $gatewayDowntimes): array
    {
        $gatewayDowntimes = $gatewayDowntimes->unique(GatewayDowntime::ISSUER);

        $gatewayDowntimes = $gatewayDowntimes->where(GatewayDowntime::ISSUER, '!=', null);

        $gatewayDowntimes = $gatewayDowntimes->whereNotIn(GatewayDowntime::ISSUER, [GatewayDowntime::UNKNOWN, GatewayDowntime::NA, GatewayDowntime::ALL]);

        return $gatewayDowntimes->pluck(GatewayDowntime::ISSUER)->toArray();
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

                if($this->shouldUseMutex()) {
                    $input = $this->getPaymentDowntimeCreationArray($gatewayDowntimes, ProviderPsp::GOOGLE_PAY, 'psp');

                    $mutexKey = $input[Entity::METHOD] . ProviderPsp::GOOGLE_PAY . $input[Entity::SCHEDULED] . $input[Entity::STATUS];

                    $this->createDowntimeWithMutex($input, $mutexKey);
                }
                else
                {
                    $this->createPaymentDowntime($gatewayDowntimes, ProviderPsp::GOOGLE_PAY, 'psp');
                }
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

    protected function createDowntimeWithMutex(array $input, string $mutexKey)
    {
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
