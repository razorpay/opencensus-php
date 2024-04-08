<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Http\Controllers\Controller;
use RZP\Services\RzpKms\KeyManagementService;
use RZP\Models\RzpKms;

class RzpKmsController extends Controller
{
    public function handleAnyPost($path = null)
    {
        $input = Request::all();

        $data = (new KeyManagementService)->sendRequest($path,'POST',$input);

        return ApiResponse::json($data);
    }


    public function createL1Workflow()
    {
        $input = Request::all();

         (new RzpKms\Service())->createL1Workflow($input);

        return ApiResponse::json();
    }

    public function createL2WorkflowTerminal()
    {
        $input = Request::all();

        (new RzpKms\Service())->createL2WorkflowTerminal($input);

        return ApiResponse::json();
    }


    public function executeL1Workflow()
    {
        $input = Request::all();

         (new RzpKms\Service())->executeL1Workflow($input);

        return ApiResponse::json();
    }

    public function executeL2WorkflowTerminal()
    {
        $input = Request::all();

        $data = (new RzpKms\Service())->executeL2WorkflowTerminal($input);

        return ApiResponse::json($data);
    }

}
