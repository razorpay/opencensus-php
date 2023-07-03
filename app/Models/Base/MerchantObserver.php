<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Detail\Core;
use Illuminate\Foundation\Application;
use RZP\Models\Base\Constants as BaseConstants;
use RZP\Models\SalesforceConverge\SalesforceConvergeService;
use RZP\Models\SalesforceConverge\SalesforceMerchantUpdatesRequest;

class MerchantObserver
{
    /**
     * The application instance.
     * @var Application
     */
    protected $app;

    /**
     * Trace instance used for tracing
     * @var Trace
     */
    protected $trace;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];
    }

    public function updated(Entity $entity)
    {
        // We just want to trigger action when the connection mode is not test
        try
        {
            if ($entity->getConnectionName() === Mode::TEST)
            {
                return;
            }

            $this->checkUpdatedAttributes($entity);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::MERCHANT_OBSERVER_ACTION_FAILURE, [
                "merchantId"   => $entity->getId(),
                "errorMessage" => $e->getMessage()
            ]);
        }
    }

    protected function checkUpdatedAttributes($entity)
    {
        if ($this->checkPaymentAttributes($entity) === true)
        {
            $isActivated = $entity->isActivated();

            $isLive = $entity->isLive();

            $properties = [
                'activated' => $isActivated,
                'live'      => $isLive
            ];

            $this->trace->info(TraceCode::MERCHANT_PAYMENT_LIVE_STATUS, [
                'properties_live_activated' => $properties
            ]);

            $this->sendSegmentEvent($properties, $entity);
        }

        if ($this->isFohUpdated($entity) === true)
        {
            $this->trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_ATTEMPT, [
                'merchantId' => $entity
            ]);

            $properties = [
                'funds_on_hold' => $entity->isFundsOnHold()
            ];

            $this->sendSegmentEvent($properties, $entity);

            $isSalesForceUpdated = (new SalesforceConvergeService())->pushUpdatesToSalesforce(new SalesforceMerchantUpdatesRequest($entity, 'FOH'));

            if ($isSalesForceUpdated === true)
            {
                $this->trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_SUCCESS, [
                    'merchantId' => $entity
                ]);
            }
        }

        if ($this->isWebsiteUpdated($entity) === true)
        {
            $businessWebsite = $entity->getWebsite();

            (new Core())->handlePluginDetails($entity, $businessWebsite);

            $this->trace->info(TraceCode::WHATCMS_KAFKA_PRODUCE_SUCCESS, [
                'merchantId' => $entity,
                'website'    => $businessWebsite
            ]);
        }
    }

    protected function isFohUpdated(Entity $entity): bool
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) > 0) and isset($dirty[BaseConstants::HOLD_FUNDS]))
        {
            return true;
        }

        return false;
    }

    protected function isWebsiteUpdated(Entity $entity): bool
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) > 0) and isset($dirty[BaseConstants::WEBSITE]))
        {
            return true;
        }

        return false;
    }

    protected function checkPaymentAttributes(Entity $entity)
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) > 0) and
            (isset($dirty[BaseConstants::ACTIVATED]) === true or
             isset($dirty[BaseConstants::LIVE]) === true))
        {
            return true;
        }

        return false;
    }

    protected function sendSegmentEvent(array $properties, $entity)
    {
        (new Core())->sendSegmentEventForFundsAndPaymentStatus($entity, $properties);
    }
}
