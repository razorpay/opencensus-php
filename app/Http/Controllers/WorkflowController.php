<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

use RZP\Models\Workflow;
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

        return ApiResponse::json($data);
    }

    public function getActionChecker(string $id, string $checkerId)
    {
        $data = (new Checker\Service)->get($id, $checkerId);

        return ApiResponse::json($data);
    }

    public function createWorkflow()
    {
        $input = Request::all();

        $data = (new Workflow\Service)->create($input);

        return ApiResponse::json($data);
    }

    public function getWorkflow(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Service)->fetch($id);

        return ApiResponse::json($data);
    }

    public function createWorkflowStep(string $id)
    {
        $input = Request::all();

        $data = (new Workflow\Step\Service)->create($id, $input);

        return ApiResponse::json($data);
    }

    public function getActionsForChecker()
    {
        $input = Request::all();

        $data = (new Checker\Service)->fetchActionsForChecker($input);

        return ApiResponse::json($data);
    }
}
