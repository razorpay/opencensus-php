<?php

namespace RZP\Models\BharatQr;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'bharat_qr';

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_REFERENCE, '=', $merchantReference)
                    ->first();
    }
}
