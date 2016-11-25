<?php

namespace RZP\Http\Controllers;

use ApiResponse;
use View;
use Cache;
use Trace;
use Request;

class UpiController extends Controller
{
    public function handle(string $api, string $id)
    {
        $body = Request::getContent();
        Trace::info('MISC_TRACE_CODE', Request::all() + ['content' => $body ] );

        file_put_contents("/home/nemo/tmp/upi-res.txt", $body);

        $xml = simplexml_load_string($body);
        $e = $xml->xpath('//Head')[0];

        $e = dom_import_simplexml($e);

        $msgId = $e->getAttribute('msgId');
        $ts = upi_ts();

        Cache::forever("UPI.$msgId", $body);

        if (in_array($api, ['RespListAccPvd', 'ReqListPsp'], true))
        {
            Cache::forever("UPI.$api", $body);
        }
        $resp = <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"><upi:Ack xmlns:upi="http://npci.org/upi/schema/" api="$api" reqMsgId="$msgId" ts="$ts"/>
EOT;
        Trace::info('GATEWAY_PAYMENT_RESPONSE', ['req'=>$body, 'ackbody' => $resp]);

        return response($resp)
            ->header('Content-Type', 'application/xml');
    }

    public function registerDevice()
    {
        $input = Request::all();

        $device = $this->generateFakeDevice($input);

        $id = $device['id'];
        $verification_id = $device['id'];

        Cache::forever("devices.$id", $device);
        Cache::forever("devices.verification.$verification_id", $id);

        return ApiResponse::json($device);
    }

    public function isDeviceVerified($deviceId)
    {
        $device = Cache::get("devices.$deviceId");

        if ($device and time() - $device['created_at'] > 10)
        {
            return ApiResponse::json(['verified'=>true, 'mobile'=>'918861670264']);
        }
        else
        {
            return ApiResponse::json(['verified'=>false,'mobile'=>'918861670264']);
        }
    }

    public function zeroCall($method)
    {
        return $this->makeGatewayRequest($method);
    }

    public function updateBankList()
    {
        return $this->makeGatewayRequest('ReqListPsp');
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

            $res[$ifsc] = [
                'ifsc'  =>  $ifsc,
                'iin'   =>  $e->getAttribute('iin'),
                'name'  =>  $e->getAttribute('name'),
                'products'  =>  explode(',',  $e->getAttribute('prods')),
                'bankname'  => $bankName,
            ];
        }

        return ApiResponse::json($res);
    }

    protected function makeGatewayRequest($method, array $params = [])
    {
        $gw = new \RZP\Gateway\Upi\Npci\Gateway;

        try
        {
            $txnId = $gw->makeRequest($method, $params);

            return ApiResponse::json(['success'=>true, 'id'=>$txnId]);
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
            'os_version'    => $input['os_version']
        ];
    }
}
