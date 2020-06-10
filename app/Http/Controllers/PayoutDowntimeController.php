<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\PayoutDowntime;

class PayoutDowntimeController extends Controller
{
    protected $service = PayoutDowntime\Service::class;

    public function createPayoutDowntime()
    {
        $input = Request::all();

        $data = $this->service()->createPayoutDowntime($input);

        return ApiResponse::json($data);
    }

    public function updatePayoutDowntime($id)
    {
        $input = Request::all();

        $data = $this->service()->editPayoutDowntime($id, $input);

        return ApiResponse::json($data);
    }

    public function fetchPayoutDowntime($id)
    {
        $data = $this->service()->fetchPayoutDowntime($id);

        return ApiResponse::json($data);
    }

    public function fetchPayoutDowntimesEnabled()
    {
        $data = $this->service()->fetchPayoutDowntimeEnabled();

        return ApiResponse::json($data);
    }

    public function fetchPayoutDowntimes()
    {
        $input = Request::all();

        $data = $this->service()->fetchPayoutDowntimes($input);

        return ApiResponse::json($data);
    }

}
