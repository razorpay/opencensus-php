<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use RZP\Models\Eligibility;
use Request;

class EligibilityController extends Controller
{
    protected $service = Eligibility\Service::class;

    public function fetchCustomerEligibility()
    {
        $input = Request::all();

        $data = $this->service()->fetchCustomerEligibility($input);

        return ApiResponse::json($data);
    }

    public function fetchCustomerEligibilityById($id)
    {
        $data = $this->service()->fetchCustomerEligibilityById($id);

        return ApiResponse::json($data);
    }

    public function fetchPublicCustomerEligibility()
    {
        $input = Request::all();

        $data = $this->service()->fetchPublicCustomerEligibility($input);

        return ApiResponse::json($data);
    }

}
