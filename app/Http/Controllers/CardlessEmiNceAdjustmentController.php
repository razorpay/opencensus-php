<?php


namespace RZP\Http\Controllers;

use ApiResponse;
use Request;
use RZP\Models\CardlessEmiNceAdjustment;

class CardlessEmiNceAdjustmentController extends Controller
{
    protected $service = CardlessEmiNceAdjustment\Service::class;
    /**
     * will be used to initiate adjustment processing
     *
     * @return ApiResponse
     */
    public function processAdjustment()
    {
        $input = Request::all();

        $response = $this->service()->initiateAdjustment($input);

        return ApiResponse::generateResponse($response);
    }
}
