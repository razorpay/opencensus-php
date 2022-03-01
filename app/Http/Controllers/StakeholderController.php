<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\HyperTrace;
use RZP\Trace\Tracer;

class StakeholderController extends Controller
{
    public function create(string $accountId)
    {
        $input = Request::all();

        $entity = Tracer::inspan(['name' => HyperTrace::CREATE_STAKEHOLDER_V2], function () use ($accountId, $input) {

            return $this->service()->create($accountId, $input);
        });

        return ApiResponse::json($entity);
    }

    public function fetch(string $accountId, string $id)
    {
        $entity = Tracer::inspan(['name' => HyperTrace::FETCH_STAKEHOLDER_V2], function () use ($accountId, $id) {

            return $this->service()->fetch($accountId, $id);
        });

        return ApiResponse::json($entity);
    }

    public function fetchAll(string $accountId)
    {
        $entity = Tracer::inspan(['name' => HyperTrace::FETCH_ALL_STAKEHOLDER_V2], function () use ($accountId) {

            return $this->service()->fetchAll($accountId);
        });

        return ApiResponse::json($entity);
    }

    public function update(string $accountId, string $id)
    {
        $input = Request::all();

        $entity = Tracer::inspan(['name' => HyperTrace::UPDATE_STAKEHOLDER_V2], function () use ($accountId, $id, $input) {

            return $this->service()->update($accountId, $id, $input);
        });

        return ApiResponse::json($entity);
    }
}
