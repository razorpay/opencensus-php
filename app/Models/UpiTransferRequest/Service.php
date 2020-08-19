<?php


namespace RZP\Models\UpiTransferRequest;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create(array $input, $requestPayload)
    {
        try
        {
            return $this->core->create($input, $requestPayload);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex);
        }
    }
}
