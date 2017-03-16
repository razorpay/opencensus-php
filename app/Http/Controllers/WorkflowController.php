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

        $data = (new Checker\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getActionCheckerMultiple(string $id)
    {
        $input = Request::all();

        $data = (new Checker\Service)->fetchMultiple($id, $input);
    }

    public function getActionChecker(string $id, string $checkerId)
    {
        $data = (new Checker\Service)->get($id, $checkerId);

        return ApiResponse::json($data);
    }
}
