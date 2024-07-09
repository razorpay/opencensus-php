<?php

namespace RZP\Http\Controllers;

use Request;
use ApiResponse;
use RZP\Constants\Entity as E;
use RZP\Base\RuntimeManager;

class GatewayFileController extends Controller
{
    public function createGatewayFile()
    {
        $input = Request::all();
        RuntimeManager::setMemoryLimit('2048M');
        RuntimeManager::setTimeLimit(15 * 60);
        RuntimeManager::setMaxExecTime(15 * 60);

        $data = $this->service(E::GATEWAY_FILE)->create($input);

        return ApiResponse::json($data);
    }

    public function acknowledgeGatewayFile(string $id)
    {
        $input = Request::all();

        $data = $this->service(E::GATEWAY_FILE)->acknowledge($id, $input);

        return ApiResponse::json($data);
    }

    public function retryGatewayFile(string $id)
    {
        $data = $this->service(E::GATEWAY_FILE)->retry($id);

        return ApiResponse::json($data);
    }

    public function uploadBankRefundFile()
    {
        $input = Request::all();

        $response = $this->service(E::GATEWAY_FILE)->uploadBankRefundFile($input);

        return ApiResponse::json($response);
    }

    public function generateCardSettlementFileForBank()
    {
        $input = Request::all();

        $data = $this->service(E::GATEWAY_FILE)->create($input);

        return ApiResponse::json($data);
    }

}
