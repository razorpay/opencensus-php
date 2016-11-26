<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

use RZP\Models\Device;
use RZP\Models\BankAccount;

class UpiController extends Controller
{
    protected $service;

    public function handle(string $api, string $id)
    {
        $body = Request::getContent();

        $this->trace->info('GATEWAY_RESPONSE', [
            'body'  =>  $body
        ]);


        $xml = simplexml_load_string($body);
        $e = $xml->xpath('//Head')[0];

        $e = dom_import_simplexml($e);

        $msgId = $e->getAttribute('msgId');
        $ts = upi_ts();

        if (in_array($api, ['RespListAccPvd', 'ReqListPsp', 'RespListKeys'], true))
        {
            if ($api === 'RespListKeys')
            {
                $type = $this->getTxnType($body);

                if ($type === 'ListKeys')
                {
                }
                else if ($type === 'GetToken')
                {
                    list($deviceId, $upiToken) = $this->parseGetTokenResponse($xml);

                    $this->trace->info('GATEWAY_RESPONSE', [
                        't' =>  $upiToken,
                        'd' =>  $deviceId,
                        'body'  =>  $body
                    ]);

                    // Update Token
                    $device = (new Device\Service)->updateUpiToken($deviceId, $upiToken);
                }
            }
        }

        $resp = <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"><upi:Ack xmlns:upi="http://npci.org/upi/schema/" api="$api" reqMsgId="$msgId" ts="$ts"/>
EOT;
        if ($api !== 'RespHbt')
        {
            Trace::info('GATEWAY_PAYMENT_RESPONSE', ['req'=>$body, 'ackbody' => $resp]);
        }

        return response($resp)
            ->header('Content-Type', 'application/xml');
    }

    public function getTxnType($str)
    {
        $xml = simplexml_load_string($str);

        $e = dom_import_simplexml($xml->xpath('//Txn')[0]);

        return $e->getAttribute('type');
    }

    public function isDeviceVerified($deviceId)
    {
        $device = Cache::get("devices.$deviceId");

        return ApiResponse::json($device);
    }

    public function zeroCall($method)
    {
        return $this->makeGatewayRequest($method);
    }

    public function updateBankList()
    {
        return $this->makeGatewayRequest('ReqListPsp');
    }

    public function getPublicKeyList()
    {
        $xml = Cache::get('UPI.ListKeys');

        return response($xml)
            ->header('Content-Type', 'application/xml');
    }

    public function readFromCache($msgId)
    {
        $xml = Cache::get("UPI.$msgId");

        return response($xml)->header('Content-Type','text/xml');
    }

    public function getBankList()
    {
        $xml = Cache::get('UPI.RespListAccPvd');

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
                    'ifsc'  =>  $ifsc,
                    'iin'   =>  $e->getAttribute('iin'),
                    'name'  =>  $e->getAttribute('name'),
                    'products'  =>  $prods,
                    'bankname'  => $bankName,
                ];
            }

        }

        return ApiResponse::json($res);
    }

    protected function makeGatewayRequest($method, array $params = [])
    {
        $gw = new \RZP\Gateway\Upi\Npci\Gateway;

        try
        {
            list($txnId, $msgId) = $gw->makeRequest($method, $params);

            return ApiResponse::json(['success'=>true, 'txnId'=>$txnId, 'msgId' => $msgId]);
        }

        catch(\Exception $e)
        {
            return ApiResponse::json(['success'=>false, 'msg' => $e->getMessage()]);
        }

    }

    public function isValidVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true,
            'available'     =>  true
        ]);
    }

    protected function parseGetTokenResponse($xml)
    {
        $deviceId = dom_import_simplexml($xml->xpath('//Txn')[0]);
        $deviceId = $deviceId->getAttribute('note');

        $token = dom_import_simplexml($xml->xpath('//keyValue')[0]);
        $token = $token->nodeValue;

        return [$deviceId, $token];
    }
}
