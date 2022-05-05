<?php

namespace RZP\Listeners;

use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;

class MerchantDetailEventListener
{
    public function onSaved(Detail\EventSaved $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::MERCHANT_SAVED_EVENT, $entity->toArray());

        $this->consumeOnSaveEvent($entity);
    }

    protected function consumeOnSaveEvent(Detail\Entity $entity)
    {
        (new Terminal\Service())->consumeInstrumentRulesEvent($entity->getId());
    }
}
