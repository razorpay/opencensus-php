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

class UpiController extends Controller
{
    protected $service;

    public function newHandle(string $api, string $id)
    {
        $core = new \RZP\Models\Upi\Core;

        $xml = $core->handleUPIRequest($api, $id, Request::getContent());

        return $this->generateXmlResponse($xml);
    }

    // TODO: Start supporting these in the new flow
    // ['RespListAccPvd', 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))/
    protected function generateXmlResponse(string $xml)
    {
        return response($xml)
            ->header('Content-Type', 'application/xml');
    }

    public function zeroCall($method)
    {
        $core = new \RZP\Models\Upi\Core;
        return $core->callUpiGateway('makeRequest', ['method' => $method, 'params' => []]);
    }

    public function getPublicKeyList()
    {
        $xml = Cache::get('UPI.ListKeys');

        return $this->generateXmlResponse($xml);
    }

    public function readFromCache($msgId)
    {
        $xml = Cache::get("UPI.$msgId");

        return $this->generateXmlResponse($xml);
    }

    public function getBankList()
    {
        $xml = Cache::get('RespListAccPvd');

        // return response($xml)->header('Content-Type', 'text/xml');

        $list = simplexml_load_string($xml)->xpath('//AccPvd');

        $res = [];

        foreach ($list as $e)
        {
            $e = dom_import_simplexml($e);

            $ifsc = $e->getAttribute('ifsc');

            try
            {
                $bankName = \RZP\Models\Bank\Name::getName($ifsc);
            }
            catch(\Exception $ex)
            {
                $bankName = "Unknown Name";
            }
            $prods = $e->getAttribute('prods');

            if ($prods === 'UPI')
            {
                $res['banks'][] = [
                    'ifsc'          =>  $ifsc,
                    'iin'           =>  $e->getAttribute('iin'),
                    'name'          =>  $e->getAttribute('name'),
                    'products'      =>  $prods,
                    'bankname'      => $bankName,
                ];
            }

        }

        return ApiResponse::json($res);
    }

    public function isValidVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true,
            'available'     =>  true
        ]);
    }
}
