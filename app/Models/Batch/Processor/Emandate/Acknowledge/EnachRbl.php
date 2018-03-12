<?php

namespace RZP\Models\Batch\Processor\Emandate\Acknowledge;

use RZP\Models\Payment\Gateway;

class EnachRbl extends Base
{
    protected $gateway = Gateway::ENACH_RBL;
}