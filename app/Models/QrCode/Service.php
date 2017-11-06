<?php

namespace RZP\Models\QrCode;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function fetchQrCode(string $id)
    {
        $qrCode = $this->repo->qr_code->findByPublicId($id);

        $response = $this->core->fetchQrCode($qrCode);

        return $response;
    }
}
