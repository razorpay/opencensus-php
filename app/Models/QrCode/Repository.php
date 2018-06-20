<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'qr_code';

    public function findByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where(Entity::REFERENCE, '=', $merchantReference)
                    ->first();
    }
}
