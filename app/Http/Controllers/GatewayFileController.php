<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity;

class GatewayFileController extends Controller
{
    public function createGatewayFile()
    {
        $input = Request::all();

        $data = $this->service(Entity::GATEWAY_FILE)->create($input);

        return ApiResponse::json($data);
    }

    public function acknowledgeGatewayFile(string $id)
    {
        $input = Request::all();

        $data = $this->service(Entity::GATEWAY_FILE)->acknowledge($id, $input);

        return ApiResponse::json($data);
    }

    public function retryGatewayFile(string $id)
    {
        $data = $this->service(Entity::GATEWAY_FILE)->retry($id);

        return ApiResponse::json($data);
    }
}
