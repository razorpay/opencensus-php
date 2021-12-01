<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Feature\Type;
use RZP\Models\Feature\Constants;
use RZP\Models\Merchant\M2MReferral\Service;
use RZP\Http\Requests\RewardValidationRequest;

class ReferralController extends Controller
{

    protected $service = Service::class;

    public function performRewardValidation(RewardValidationRequest $request)
    {
        $this->service()->performRewardValidation($request);

        return ApiResponse::json([]);
    }

    public function fetchReferralDetails()
    {
        $input = Request::all();

        $response = $this->service()->fetchReferralDetails();

        return ApiResponse::json($response);
    }

    public function fetchPublicReferralDetails()
    {
        $input = Request::all();

        $response = $this->service()->fetchPublicReferralDetails();

        return ApiResponse::json($response);
    }
}
