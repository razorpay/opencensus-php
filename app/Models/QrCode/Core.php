<?php

namespace RZP\Models\QrCode;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\FileStore;

class Core extends Base\Core
{
    public function fetchQrCode(Entity $qrCode, Merchant\Entity $merchant)
    {
        $qrCodeImage = $qrCode->qrCodeFile();

        return (new FileStore\Accessor)
                    ->id($qrCodeImage->getId())
                    ->merchantId($merchant->getId())
                    ->getFile();
    }
}
