<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use Request;

use RZP\Constants\Entity;

class RiskController extends Controller
{
    public function post()
    {
        $input = Request::all();

        $data = $this->service(Entity::RISK)->create($input);

        return ApiResponse::json($data);
    }

    public function put(string $id)
    {
        $input = Request::all();

        $data = $this->service(Entity::RISK)->edit($id, $input);

        return ApiResponse::json($data);
    }

    public function fetch(string $id)
    {
        $data = $this->service(Entity::RISK)->fetch($id);

        return ApiResponse::json($data);
    }

    public function fetchMultiple()
    {
        $input = Request::all();

        $data = $this->service(Entity::RISK)->fetchMultiple($input);

        return ApiResponse::json($data);
    }
}
