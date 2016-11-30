<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

use RZP\Models\Device;
use RZP\Models\BankAccount;
use RZP\Models\Customer;
use RZP\Models\Upi;

class UpiController extends Controller
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Upi\Core;
    }

    public function newHandle(string $api, string $id)
    {
        $xml = $this->core->handleUPIRequest($api, $id, Request::getContent());

        return $this->generateXmlResponse($xml);
    }

    public function zeroCall($method)
    {
        return $this->core->callUpiGateway('makeRequest', ['method' => $method, 'params' => []]);
    }

    public function getPublicKeyList()
    {
        $xml = Cache::get('UPI.ListKeys');

        return $this->generateXmlResponse($xml);
    }

    public function getStatus($originalMsgId)
    {
        $json =  Cache::get("UPI.$originalMsgId.response");

        if (isset($json['success']))
        {
            $json['success'] = ($json['success'] === 'SUCCESS');
        }

        return ApiResponse::json($json);
    }

    public function getBankList()
    {
        $json = json_decode(Cache::get('UPI.RespListAccPvd'), true);

        return ApiResponse::json($json);
    }

    public function getVpas()
    {
        $input = Request::all();

        $data = $this->core->getVpas($input);

        return ApiResponse::json($data);
    }

    public function createVpa()
    {
        $input = Request::all();

        $data = $this->core->createVpa($input);

        return ApiResponse::json($data);
    }

    public function deleteVpa($id)
    {
        $data = $this->core->deleteVpa($id);

        return ApiResponse::json($data);
    }

    public function editVpa($id)
    {
        $input = Request::all();

        $data = $this->core->editVpa($id, $input);

        return ApiResponse::json($data);
    }

    public function isValidVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true
        ]);
    }

    public function isAvailableVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true,
            'available'     =>  true
        ]);
    }

    // TODO: Start supporting these in the new flow
    // 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))/
    protected function generateXmlResponse(string $xml)
    {
        return response($xml)->header('Content-Type', 'application/xml');
    }
}
