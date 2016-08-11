<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Payment;

class Service extends Base\Service
{
    protected $core;
    
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }
    
    public function create($input)
    {
       // TODO: Fill this up 
    }
}