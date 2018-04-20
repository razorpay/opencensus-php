<?php

namespace RZP\Gateway\Card\Fss;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'card_fss';

    // TODO: Rename the function to a proper one
    // and fix the get auth code function for emi
    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::AUTHORIZE)
                    ->firstOrFail();
    }
}
