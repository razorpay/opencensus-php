<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Merchant;

class PartnerController extends Controller
{
    protected $service = Merchant\Service::class;

    /**
     * @param string $partnerId
     *
     * @return \Illuminate\Http\Response
     */
    public function createReferral(string $partnerId)
    {
        $input = $this->input;

        $response = $this->service()->createPartnerReferral($partnerId, $input);

        return ApiResponse::json($response);
    }

    /**
     * @param string $partnerId
     * @param string $referralId
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteReferral(string $partnerId, string $referralId)
    {
        $response = $this->service()->deletePartnerReferral($partnerId, $referralId);

        return ApiResponse::json($response);
    }
}
