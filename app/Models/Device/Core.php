<?php

namespace RZP\Models\Device;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Upi;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @var Upi\Core
     */
    protected $upiCore;

    public function __construct()
    {
        parent::__construct();

        $this->upiCore = new Upi\Core;
    }

    public function create(array $input)
    {
        $device = new Entity();

        $device->build($input);

        $this->repo->saveOrFail($device);

        return $device;
    }

    public function verifyAndGetToken(array $input)
    {
        $response = $this->upiCore->callUpiGateway('upi_npci', 'GetToken', $input);

        return $response;
    }
}
