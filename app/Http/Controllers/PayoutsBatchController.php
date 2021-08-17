<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Payout\Batch as PayoutsBatch;

use RZP\Http\Controllers\Traits\HasCrudMethods;

class PayoutsBatchController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = (new PayoutsBatch\Service());
    }

    use HasCrudMethods;
}
