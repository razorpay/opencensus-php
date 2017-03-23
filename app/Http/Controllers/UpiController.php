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
    public function newHandle(string $api, string $id)
    {
        $this->core = new Upi\Core;

        $body = Request::getContent();
        if (substr($id, 0, 3) == 'RAY')
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
        $this->core = new Upi\Core;

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
        $this->core = new Upi\Core;

        $data = $this->core->getVpas();

        return ApiResponse::json($data);
    }

    public function getVpa(string $id)
    {
        $this->core = new Upi\Core;

        $data = $this->core->getVpa($id);

        return ApiResponse::json($data);
    }

    public function getVpaPrivate(string $id)
    {
        $this->core = new Upi\Core;

        $data = $this->core->getVpaPrivate($id);

        return ApiResponse::json($data);
    }

    public function deleteVpa($id)
    {
        $this->core = new Upi\Core;

        $data = $this->core->deleteVpa($id);

        return ApiResponse::json($data);
    }

    public function editVpa($id)
    {
        $input = Request::all();

        $this->core = new Upi\Core;

        $data = $this->core->editVpa($id, $input);

        return ApiResponse::json($data);
    }

    public function isValidVpa($vpa)
    {
        $this->core = new Upi\Core;

        $data = $this->core->isValidVpa($vpa);

        return ApiResponse::json($data);
    }

    public function isAvailableVpa($vpa)
    {
        $this->core = new Upi\Core;

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

        $this->core = new Upi\Core;

        $data = $this->core->disallowVpaPsp($input);

        return ApiResponse::json($data);
    }

    public function postPspAllow()
    {
        $input = Request::all();

        $this->core = new Upi\Core;

        $data = $this->core->allowVpaPsp($input);

        return ApiResponse::json($data);
    }
}
