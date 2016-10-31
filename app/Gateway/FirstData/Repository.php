<?php

namespace RZP\Gateway\FirstData;

use RZP\Exception;
use RZP\Gateway\FirstData;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'first_data';

    public function findCapturedPaymentByIdOrFail($paymentId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Entity::ACTION, '=', Base\Action::CAPTURE)
                    ->firstOrFail();
    }
}
