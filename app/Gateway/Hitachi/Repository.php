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
}
