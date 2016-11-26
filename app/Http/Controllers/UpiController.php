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

        if (in_array($api, ['RespListAccPvd', 'ReqListPsp', 'RespListKeys', 'ReqRegMob'], true))
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
            else if ($api === 'ReqRegMob')
            {
                $creds = $this->parseSetMpinResponse($xml);

                (new Customer\Service)->setMPINForBankAccounts($creds['bank_account_number'], $creds['mpin']);

                $this->fireRespRegMobResponse($msgId);
            }
            else
            {
                Cache::forever($api, $body);
            }
        }

        $resp = <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"><upi:Ack xmlns:upi="http://npci.org/upi/schema/" api="$api" reqMsgId="$msgId" ts="$ts"/>
EOT;
        if ($api !== 'RespHbt')
        {
            Trace::info('GATEWAY_PAYMENT_RESPONSE', ['req'=>$body, 'ackbody' => $resp]);
        }

        return $this->generateXmlResponse($resp);
    }

    protected function generateXmlResponse(string $xml)
    {
        return response($xml)
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

    protected function makeGatewayRequest($method, array $input = [])
    {
        $gw = new \RZP\Gateway\Upi\Npci\Gateway;

        // try
        // {
            $params = [];
            $params['method'] = $method;
            $params['params'] = $input;
            $response = $gw->makeRequest($params);

            return ApiResponse::json(['success'=>true] + $response);
        // }

        // catch(\Exception $e)
        // {
        //     return ApiResponse::json(['success'=>false, 'msg' => $e->getMessage()]);
        // }

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

    protected function parseSetMpinResponse($xml)
    {
        $bankAccount = dom_import_simplexml($xml->xpath('//Txn')[0]);
        $bankAccountNumber = $bankAccount->getAttribute('note');

        $last6 = (dom_import_simplexml($xml->xpath('//RegDetails/Detail[@name="CARDDIGITS"]')[0])->getAttribute('value'));

        $expiry = (dom_import_simplexml($xml->xpath('//RegDetails/Detail[@name="EXPDATE"]')[0])->getAttribute('value'));

        $otp = trim(dom_import_simplexml($xml->xpath('//Cred[@type="OTP"]/Data')[0])->nodeValue);
        $mpin = trim(dom_import_simplexml($xml->xpath('//Cred[@type="PIN"]/Data')[0])->nodeValue);

        return [
            'bank_account_number'   =>  $bankAccountNumber,
            'otp'                   =>  $otp,
            'pin'                   =>  $mpin,
            'last6'                 =>  $last6,
            'expiry'                =>  $expiry,
        ];
    }

    protected function fireRespRegMobResponse($msgId)
    {
        $core = new \RZP\Models\UPI\Core;
        $arr = [
            'params'    =>  ['reqMsgId' => $msgId],
            'method'    =>  'RespRegMob'
        ];
        $xml = $core->callUpiGateway('upi_npci', 'makeRequest', $arr);

        return $this->generateXmlResponse($xml);
    }
}
