<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Workflow\Action\Checker;

class WorkflowController extends Controller
{
    public function postActionChecker(string $id)
    {
        $input = Request::all();

        (new Checker\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getActionCheckerMultiple(string $id)
    {
    }

    public function getActionChecker(string $id, string $checkerId)
    {}
}
