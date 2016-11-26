<?php

namespace RZP\Http\Controllers;

use RZP\Models\Upi\Service;
use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

class UpiController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new Service;
    }

    public function handle(string $api, string $id)
    {
        $body = Request::getContent();

        $xml = simplexml_load_string($body);
        $e = $xml->xpath('//Head')[0];

        $e = dom_import_simplexml($e);

        $msgId = $e->getAttribute('msgId');
        $ts = upi_ts();

        Cache::forever("UPI.$msgId", $body);
        Cache::forever("UPI.$id", $body);

        if (in_array($api, ['RespListAccPvd', 'ReqListPsp', 'RespListKeys'], true))
        {
            $cache = $api;
            if ($api === 'RespListKeys')
            {
                $type = $this->getTxnType($body);

                if ($type === 'ListKeys')
                {
                    $cache = 'ListKeys';
                }
                else if ($type === 'GetToken')
                {
                    $device = Cache::get("UPI.req.$id");

                    // Update Token
                    $this->updateDeviceToken($xml);
                    $cache = false;
                }
            }

            if ($cache)
            {
                Cache::forever("UPI.$cache", $body);
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

    protected function updateDeviceToken($xml)
    {
        $deviceId = 'knpVYacquVfRKQaw';
        $device = Cache::get("devices.$deviceId");
        $e = dom_import_simplexml($xml->xpath('//keyValue')[0]);

        $token = $e->nodeValue;

        $device['token'] = $token;

        Cache::forever("devices.$deviceId", $device);
    }

    public function getTxnType($str)
    {
        $xml = simplexml_load_string($str);

        $e = dom_import_simplexml($xml->xpath('//Txn')[0]);

        return $e->getAttribute('type');
    }

    public function registerDevice()
    {
        $input = Request::all();

        $device = $this->generateFakeDevice($input);

        $id = $device['id'];
        $verification_sms = $device['verification'];

        Cache::forever("devices.$id", $device);
        Cache::forever("devices.verification.$verification_sms", $id);

        return ApiResponse::json($device);
    }

    public function verifyDevice()
    {
        $input = Request::all();

        Trace::info('MISC_TRACE_CODE', $input);

        // Msg91 converts the keyword to lowecase
        $keyword = trim(strtoupper($input['keyword']))

        if ($keyword === 'VERIFY')
        {
            $msg = $input['message'];
            $mobile = $input['number'];
        }

        ApiResponse::json($input);
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

    protected function generateFakeDevice(array $input)
    {
        return [
            'id'            => str_random(16),
            'verification'  => str_random(16),
            'created_at'    => time(),
            'device_id'     => $input['device_id'],
            'os_version'    => $input['os_version'],
            'challenge'     => $input['challenge'],
            'app_id'        => $input['app_id'],
        ];
    }

    public function isValidVpa($vpa)
    {
        return ApiResponse::json([
            'valid'         =>  true,
            'available'     =>  true
        ]);
    }

    public function getBankAccountList()
    {
        $bas = [];
        $bas['accounts'][] = [
            'ifsc'          =>  'PUNB',
            'account'       =>  '1235543534543543',
            'type'          =>  'SAVINGS',
            'bank_name'     =>  'Punjab National Bank',
        ];

        return ApiResponse::json($bas);
    }
}
