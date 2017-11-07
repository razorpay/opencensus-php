<?php

namespace RZP\Models\QrCode;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;

class Core extends Base\Core
{
    public function fetchQrCode(Entity $qrCode)
    {
        $qrCodeImage = $qrCode->qrCodeFile();

        return (new FileStore\Accessor)
                    ->id($qrCodeImage->getId())
                    ->merchantId($qrCode->getMerchantId())
                    ->getFile();
    }
}
