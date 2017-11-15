<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * This return qr code file
     * path downloading the qr code
     *
     * @param string $id
     *
     * @return string
     */
    public function fetchQrCode(string $id)
    {
        $qrCode = $this->repo->qr_code->findByPublicId($id);

        $qrCodeFilePath = $this->core()->fetchQrCode($qrCode, $qrCode->merchant);

        return $qrCodeFilePath;
    }
}
