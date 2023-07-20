<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Exception;
use RZP\Models\Payment\Method;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\Gateway;
use RZP\Jobs\PaymentDowntimeEvent;
use RZP\Gateway\Upi\Base\ProviderPsp;
use RZP\Models\Gateway\Downtime\Severity;
use RZP\Models\Payment\Downtime\Constants;
use RZP\Models\Gateway\Downtime\ReasonCode;
use RZP\Constants\Entity as EntityConstants;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Gateway\Downtime\Entity as GatewayDowntime;
use RZP\Services\RazorpayLabs\SlackApp as SlackAppService;

class BaseProcessor extends Base\Core
{
    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }


    protected function endOngoingDowntimes(array $unavailableList = [], string $mid = null)
    {
        $ongoingDowntimes = $this->getRepo()->fetchOngoingDowntimesByMethodAndMerchant($this->method, $mid);

        $ongoingDowntimes = $ongoingDowntimes->where(Entity::PSP, '!=', ProviderPsp::GOOGLE_PAY);

        /**
         * Filter out all the downtimes which should be resolved by checking the unavailable list
         * `issuer` in case of nebanking, fpx and wallet
         * `network` in case of card
         *
         * This insures that downtime for `issuers` which are available now i.e which are not in unavailable list
         * gets resolved
         */
        if (array_key_exists($this->method, Constants::METHOD_QUERY_MAP) === true)
        {
            $attributes = Constants::getMethodQueryInstrument($this->method);

            foreach ($attributes as $attribute)
            {
                $ongoingDowntimes = $ongoingDowntimes->whereNotIn($attribute, $unavailableList);
            }
        }

        $this->endDowntime($ongoingDowntimes);
    }

    protected function endOngoingDowntimesForMerchants(array $merchantsWithDowntime)
    {
        $ongoingDowntimes = $this->getRepo()->fetchOngoingDowntimesByMethodForMerchantsWithoutDowntimes($this->method, $merchantsWithDowntime);

        $this->endDowntime($ongoingDowntimes);
    }

    protected function endDowntime($ongoingDowntimes)
    {
        foreach ($ongoingDowntimes as $downtime) {
            $downtime->setEndNow();

            $downtime->setStatus(Status::RESOLVED);

            $this->getRepo()->saveOrFail($downtime);

            $this->trace->info(
                TraceCode::PAYMENT_DOWNTIME_RESOLVE,
                $downtime->toArray()
            );

            (new Core)->refreshOngoingDowntimesCache($downtime);

            (new Core)->refreshHistoricalDowntimeCache(3);

            if(($downtime->getMethod() !== Method::EMANDATE)){
                (new Service())->emailDowntime(Constants::RESOLVED, $downtime);

                PaymentDowntimeEvent::dispatch($this->mode, Status::RESOLVED, serialize($downtime));

                (new DowntimeManagerService($this->app))->notifyDowntime($downtime, Status::RESOLVED);
                (new SlackAppService($this->app))->sendDowntimeRequestToSlack($downtime, Status::RESOLVED);
            }
        }
    }

    protected function createDowntime($input)
    {
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

    protected function calculateDowntimeScheduled(Collection $gatewayDowntimes): bool
    {
        return $gatewayDowntimes->every(GatewayDowntime::SCHEDULED, '=', true);
    }

    protected function calculateDowntimeSeverity(Collection $gatewayDowntimes): string
    {
        $sortedBySource = $gatewayDowntimes->sort(function ($a, $b){
            $sourceA = $a->getSource();
            $sourceB = $b->getSource();

            return Severity::PRECEDENCE_SOURCE[$sourceA] - Severity::PRECEDENCE_SOURCE[$sourceB];
        });

        $reasonCode = $sortedBySource->first()->getReasonCode();

        return ReasonCode::getSeverity($reasonCode);
    }

    protected function getDuplicate(array $input)
    {
        return $this->getRepo()->getDuplicate($input);
    }

    protected function getOverlappingDowntimePeriod(Collection $gatewayDowntimes)
    {
        $gatewayDowntimes = $gatewayDowntimes->sortBy(GatewayDowntime::BEGIN);

        $gatewayDowntimes = $gatewayDowntimes->unique(GatewayDowntime::GATEWAY);

        $gatewayDowntimes = array_values($gatewayDowntimes->toArray());

        $beginTime = $gatewayDowntimes[0][GatewayDowntime::BEGIN];

        $endTime = $gatewayDowntimes[0][GatewayDowntime::END];

        for ($idx = 1; $idx < count($gatewayDowntimes); $idx++)
        {
            if (($endTime === null) or
                ($endTime > $gatewayDowntimes[$idx][GatewayDowntime::BEGIN]))
            {
                $beginTime = $gatewayDowntimes[$idx][GatewayDowntime::BEGIN];

                $endTime = min($endTime, $gatewayDowntimes[$idx][GatewayDowntime::END]);
            }
            else
            {
                $beginTime = null;

                break;
            }
        }

        return [$beginTime, $endTime];
    }

    protected function getRepo()
    {
        return $this->repo->getCustomDriver(EntityConstants::PAYMENT_DOWNTIME);
    }

    protected function shouldUseMutex():bool
    {
        return (bool) ConfigKey::get(ConfigKey::USE_MUTEX_FOR_DOWNTIMES, false);
    }
}
