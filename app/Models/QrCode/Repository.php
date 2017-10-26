<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'qr_code';

    public function findById($qrCodeId)
    {
        return $this->newQuery()
                    ->where(Entity::ID, '=', $qrCodeId)
                    ->first();
    }
}
