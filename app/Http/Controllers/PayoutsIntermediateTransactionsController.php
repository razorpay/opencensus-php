<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Payout\PayoutsIntermediateTransactions\Service;

class PayoutsIntermediateTransactionsController extends Controller
{
    protected $service = Service::class;

    public function updatePayoutIntermediateTransactions()
    {
        $data = $this->service()->updatePayoutIntermediateTransactions();

        return ApiResponse::json($data);
    }
}
