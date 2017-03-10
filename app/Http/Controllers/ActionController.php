<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Action;

class ActionController extends Controller
{
    protected $action;

    public function __construct()
    {
        parent::__construct();

        $this->action = new Action\Service;
    }

    public function postCreateAction(string $entity, string $entityId)
    {
        $input = Request::all();

        $result = $this->action->createAction($entity, $entityId, $input);

        return ApiResponse::json($result);
    }

    public function fetchDiffById(string $id)
    {
        $result = $this->action->fetchDiffById($id);

        return ApiResponse::json($result);
    }

    public function postExecuteAction(string $id)
    {
        $result = $this->action->execute($id);

        return ApiResponse::json($result);
    }
}
