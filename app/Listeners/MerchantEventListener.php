<?php

namespace RZP\Listeners;

use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class MerchantEventListener
{
    public function onSaved(Merchant\EventSaved $event)
    {
        $entity = $event->entity;

        app('trace')->info(TraceCode::MERCHANT_SAVED_EVENT, $entity->toArray());

        $this->consumeOnSaveEvent($entity);
    }

    protected function consumeOnSaveEvent(Merchant\Entity $entity)
    {
        (new Terminal\Service())->consumeInstrumentRulesEvent($entity->getId());
    }
}
