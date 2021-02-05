<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;

class StakeholderController extends Controller
{
    public function create(string $accountId)
    {
        $input = Request::all();

        $entity = $this->service()->create($accountId, $input);

        return ApiResponse::json($entity);
    }

    public function fetch(string $accountId, string $id)
    {
        $entity = $this->service()->fetch($accountId, $id);

        return ApiResponse::json($entity);
    }

    public function fetchAll(string $accountId)
    {
        $entity = $this->service()->fetchAll($accountId);

        return ApiResponse::json($entity);
    }

    public function update(string $accountId, string $id)
    {
        $input = Request::all();

        $entity = $this->service()->update($accountId, $id, $input);

        return ApiResponse::json($entity);
    }
}
