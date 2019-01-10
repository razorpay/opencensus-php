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
        $body = Request::getContent();

        if (substr($id, 0, 3) === 'RAY')
        {
            $forwardUrl = 'http://api2.razorpay.in/' . Request::path();

            $response = \Requests::post($forwardUrl, [
                'Content-Type'  => 'application/xml'
            ], $body);

            return $this->generateXmlResponse($response->body);
        }
        else
        {
            $xml = (new Upi\Core)->handleUPIRequest($api, $id, $body);

            return $this->generateXmlResponse($xml);
        }
    }

    public function zeroCall($method)
    {
        return (new Upi\Core)->callUpiGateway('makeRequest', ['method' => $method, 'params' => []]);
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
            'success' => false,
            'error'   => [],
            'pending' => true
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

    // TODO: Start supporting these in the new flow
    // 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))/
    protected function generateXmlResponse(string $xml)
    {
        return response($xml)->header('Content-Type', 'application/xml');
    }

    public function postPspDisallow()
    {
        $input = Request::all();

        $data = (new Upi\Core)->disallowVpaPsp($input);

        return ApiResponse::json($data);
    }

    public function postPspAllow()
    {
        $input = Request::all();

        $data = (new Upi\Core)->allowVpaPsp($input);

        return ApiResponse::json($data);
    }
}
