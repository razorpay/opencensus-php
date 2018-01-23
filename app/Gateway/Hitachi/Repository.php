<?php

namespace RZP\Gateway\Hitachi;

use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'hitachi';

    public function fetchByQrCodeId($qrCodeId)
    {
        return $this->newQuery()
            ->where('qr_code_id' , '=', $qrCodeId)
            ->first();
    }

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
