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
    public function fetchQrCodePath(string $id)
    {
        // Can't use merchant here because this is a direct route
        $qrCode = $this->repo->qr_code->findByPublicId($id);

        $qrCodeFilePath = $this->core()->fetchQrCodePath($qrCode, $qrCode->merchant);

        return $qrCodeFilePath;
    }
}
