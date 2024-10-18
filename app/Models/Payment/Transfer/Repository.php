<?php

namespace RZP\Models\Payment\Transfer;

use App;
use DB;
use RZP\Models\Base\Traits\ExternalLinkedAccountPaymentRepo;
use RZP\Models\Payment;


class Repository extends Payment\Repository
{
    use ExternalLinkedAccountPaymentRepo;
}
