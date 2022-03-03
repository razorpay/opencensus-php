<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\PayoutOutbox\Service as PayoutOutboxService;

class PayoutOutboxController extends Controller
{
    use Traits\HasCrudMethods;

    public function createPayoutOutboxPartition()
    {
        $response = (new PayoutOutboxService())->createPayoutOutboxPartition();

        return ApiResponse::json($response);
    }
}
