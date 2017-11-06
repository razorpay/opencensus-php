<?php

namespace RZP\Models\QrCode;

use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function fetchQrCode(Entity $qrCode)
    {
        return (new FileStore\Accessor)
                    ->id($qrCode->getId())
                    ->merchantId($qrCode->getMerchantId())
                    ->getFile();
    }
}
