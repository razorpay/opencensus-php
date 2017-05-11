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
        $body = Request::getContent();
        if (substr($id, 0, 3) === 'RAY')
        {
            $forwardUrl = 'http://api2.razorpay.dev/' . Request::path();

            $response = \Requests::post($forwardUrl, [
                'Content-Type'  => 'application/xml'
            ], $body);

            return $this->generateXmlResponse($response->body);
        }
        else
        {
            $xml = $this->core->handleUPIRequest($api, $id, $body);

            return $this->generateXmlResponse($xml);
        }
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

    public function getStatus($Id)
    {
        $key = "UPI.$Id.response";

        $json =  Cache::get($key) ?? [
            'success'   =>  false,
            'error' =>  [],
            'pending'   =>  true
        ];

        if (!isset($json['pending']))
        {
            $json['pending'] = false;
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
        $data = $this->core->getVpas();

        return ApiResponse::json($data);
    }

    public function getVpa(string $id)
    {
        $data = $this->core->getVpa($id);

        return ApiResponse::json($data);
    }

    public function getVpaPrivate(string $id)
    {
        $data = $this->core->getVpaPrivate($id);

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
        $data = $this->core->isValidVpa($vpa);

        return ApiResponse::json($data);
    }

    public function isAvailableVpa($vpa)
    {
        $data = $this->core->isAvailableVpa($vpa);

        return ApiResponse::json($data);
    }

    // TODO: Start supporting these in the new flow
    // 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))/
    protected function generateXmlResponse(string $xml)
    {
        return response($xml)->header('Content-Type', 'application/xml');
    }

    public function postPspDisallow()
    {
        $input = Request::all();

        $data = $this->core->disallowVpaPsp($input);

        return ApiResponse::json($data);
    }

    public function postPspAllow()
    {
        $input = Request::all();

        $data = $this->core->allowVpaPsp($input);

        return ApiResponse::json($data);
    }
}
