<?php

namespace RZP\Models\QrPaymentRequest;

use RZP\Models\Base;
class Repository extends Base\Repository
{
    protected $entity = 'qr_payment_request';

    public function fetchPaymentReference($reference_id){
        return $this->newQuery()
            ->where(Entity::TRANSACTION_REFERENCE, '=', $reference_id)
            ->first();
    }
}

