<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Trace\TraceCode;
use RZP\Models\VirtualAccount;

class VirtualAccountController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = VirtualAccount\Service::class;

    public function getPayments(string $id)
    {
        $response = $this->service()->fetchPayments($id);

        return ApiResponse::json($response);
    }

    public function refundExcessPayments()
    {
        $data = $this->service()->refundExcessPayments();

        return ApiResponse::json($data);
    }
}
