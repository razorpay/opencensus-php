<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\SalesforceConverge\SalesforceConvergeService;
use RZP\Models\SalesforceConverge\SalesforceMerchantUpdatesRequest;
use RZP\Models\Merchant\Detail\Core as DetailCore;

class MerchantObserver
{
    const HOLD_FUNDS = 'hold_funds';

    const WEBSITE    = 'website';

    public function updated(Entity $entity)
    {

        $trace = App::getFacadeRoot()['trace'];

        try
        {
            if ($entity->getConnectionName() == Mode::TEST or ($this->isFohUpdated($entity) === false
                                                               and $this->isWebsiteUpdated($entity) === false))
            {
                return;
            }

            $trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_ATTEMPT,
                         [
                             'merchantId' => $entity
                         ]);

            if($this->isFohUpdated($entity) === true)
            {
                $retval = (new SalesforceConvergeService())->pushUpdatesToSalesforce(new SalesforceMerchantUpdatesRequest($entity, 'FOH'));

                if ($retval == true)
                {
                    $trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_SUCCESS,
                                 [
                                     'merchantId' => $entity
                                 ]);
                }
            }

            if($this->isWebsiteUpdated($entity) === true)
            {
                $businessWebsite = $entity->getWebsite();

                (new DetailCore())->handlePluginDetails($entity, $businessWebsite);

                $trace->info(TraceCode::WHATCMS_KAFKA_PRODUCE_SUCCESS,
                             [
                                 'merchantId' => $entity,
                                 'website'    => $businessWebsite
                             ]);
            }

        }
        catch (\Throwable $e)
        {
            $trace->traceException($e, Trace::ERROR,
                                   TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_ERROR,
                                   [
                                       "merchantId" => $entity->getId()
                                   ]
            );
        }
    }


    protected function isFohUpdated(Entity $entity): bool
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) > 0) and isset($dirty[self::HOLD_FUNDS]))
        {
            return true;
        }

        return false;
    }

    protected function isWebsiteUpdated(Entity $entity): bool
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) > 0) and isset($dirty[self::WEBSITE]))
        {
            return true;
        }

        return false;
    }
}
