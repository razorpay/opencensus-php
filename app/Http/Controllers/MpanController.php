<?php


namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Mpan;

class MpanController extends Controller
{
    protected $service = Mpan\Service::class;

    public function issueMpans()
    {
        $input = Request::all();

        $mpanCollection = $this->service()->issueMpans($input);

        return ApiResponse::json($mpanCollection);
    }

    /**
     * @return The mpans that have been assigned to the requesting merchant
     */
    public function fetchMpans()
    {
        $input = Request::all();

        $mpanCollection = $this->service()->fetchMpans($input);

        return ApiResponse::json($mpanCollection);
    }

    public function postMpansBulk()
    {
        $input = Request::all();

        $response = $this->service()->mpansBulk($input);

        return ApiResponse::json($response->toArrayWithItems());
    }

    public function postTokenizeMpans()
    {
        $input = Request::all();

        $cronResponse = $this->service()->tokenizeExistingMpans($input);
        
        return ApiResponse::json($cronResponse);
    }

}
