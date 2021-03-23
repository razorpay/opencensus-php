<?php


namespace RZP\Http\Controllers;
use Request;

use ApiResponse;

class SegmentationController extends Controller
{
    public function segmentPopulate(){
        $input = Request::all();
        $response = $this->service()->segmentPopulate($input);
        return ApiResponse::json($response);
    }
}
