<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class PartnerController extends Controller
{
    protected $service = Merchant\Service::class;

    public function createReferral(string $partnerId)
    {
        $input = $this->input;

        $response = $this->service()->createPartnerReferral($partnerId, $input);

        return ApiResponse::json($response);
    }
}
