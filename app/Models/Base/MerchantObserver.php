<?php

namespace RZP\Models\Base;

use App;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\SalesforceConverge\SalesforceConvergeService;
use RZP\Models\SalesforceConverge\SalesforceMerchantUpdatesRequest;

class MerchantObserver
{
    const HOLD_FUNDS = 'hold_funds';

    public function updated(Entity $entity)
    {

        $trace = App::getFacadeRoot()['trace'];

        try
        {
            if ($entity->getConnectionName() == Mode::TEST or $this->isFohUpdated($entity) === false)
            {
                return;
            }

            $trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_ATTEMPT,
                         [
                             'merchantId' => $entity
                         ]);

            $retval = (new SalesforceConvergeService())->pushUpdatesToSalesforce(new SalesforceMerchantUpdatesRequest($entity, 'FOH'));

            if ($retval == true)
            {
                $trace->info(TraceCode::SALESFORCE_CONVERGE_FOH_TRIGGER_SUCCESS,
                             [
                                 'merchantId' => $entity
                             ]);
            }
        } catch (\Throwable $e)
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
}
