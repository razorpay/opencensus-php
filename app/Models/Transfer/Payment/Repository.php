<?php

namespace RZP\Models\Transfer\Payment;

use RZP\Constants;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = Constants\Entity::TRANSFER_PAYMENT;

    public function getTransferPayment($paymentID)
    {
       return $this->newQuery()
                    ->where(Entity::PAYMENT_ID, $paymentID)
                    ->get(); 
    }
}
