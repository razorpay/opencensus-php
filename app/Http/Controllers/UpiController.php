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

    public function getVpas()
    {

    }

    public function vpaAvailable($vpaId)
    {

    }

    public function isValidVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true,
            'available'     =>  true
        ]);
    }

    // TODO: Start supporting these in the new flow
    // ['RespListAccPvd', 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))/
    protected function generateXmlResponse(string $xml)
    {
        return response($xml)->header('Content-Type', 'application/xml');
    }
}
