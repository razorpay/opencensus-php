<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Models\Action;

class ActionController extends Controller
{
    protected $actionService;

    public function __construct()
    {
        parent::__construct();

        $this->actionService = new Action\Service;
    }

    public function createAction(string $entity, string $entityId)
    {
        $input = Request::all();

        $result = $this->actionService->createAction($entity, $entityId, $input);

        return ApiResponse::json($result);
    }
}
