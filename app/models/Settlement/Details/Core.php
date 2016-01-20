<?php

namespace Models\Settlement\Detail;

use Models\Base;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->repo = new Merchant\Repository;
    }
}