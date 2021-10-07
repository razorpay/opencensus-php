<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\BulkWorkflowAction\Service;

class BulkActionController extends Controller
{
    public function executeBulkAction()
    {
        $input = Request::all();
        $data = (new Service())->executeBulkAction($input);
        return ApiResponse::json($data);
    }

    public function addBulkRiskActionCommentPostExecution()
    {
        $input = Request::all();
        $data = (new Service())->addBulkRiskActionCommentPostExecution($input);
        return ApiResponse::json($data);
    }
}
