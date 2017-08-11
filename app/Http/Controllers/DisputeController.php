<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Dispute;

class DisputeController extends Controller
{
    use Traits\HasCrudMethods;

    protected $service = Dispute\Service::class;

    public function create(string $paymentId)
    {
        $input = Request::all();

        $data = $this->service()->create($input, $paymentId);

        return ApiResponse::json($data);
    }
}
