<?php

namespace RZP\Models\Invoice;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }
    
    public function create($input)
    {
        $invoice = $this->core->create($input);

        return $invoice->toArrayPublic();
    }
    
    public function fetch($id)
    {
        $invoice = $this->core->retrieveByIdAndMerchantId($id, $this->merchant->getId());

        return $invoice->toArrayPublic();
    }
}