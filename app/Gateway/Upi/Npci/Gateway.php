<?php

namespace RZP\Gateway\Upi\Npci;

use Cache;
use Carbon\Carbon;
use RZP\Gateway\Upi\Base;
use ErrorException;
use phpseclib\Crypt\RSA;
use RZP\Trace\TraceCode;
use Trace;
use Requests;
use AppResponse;

class Gateway extends Base\Gateway
{
    // These are various requests that
    // we need to process in some form
    // before we send an Ack response
    const PROCESSABLE_REQUESTS = [
        // Someone is asking for list of accounts
        'ReqListAccount',
        // Someone returned us keys for an earlier request
        'RespListKeys',
        // Someone is trying to set MPIN!
        'ReqRegMob',
        // We got a list
        'RespListAccPvd',
        // Someone wants to reset MPIN
        'ReqSetCre',
    ];

    protected $gateway = 'upi_npci';

    protected function getCommonVariables()
    {
        return [
            'txnId'     => upi_uuid(),
            'ids'       => [upi_uuid(), upi_uuid()],
            'ts'        => upi_ts(),
            'msgId'     => upi_uuid(),
            'refUrl'    => "http://www.npci.org.in/",
            'orgId'     => 'RAZOR',
        ];
    }

    protected function needsProcessing(string $api): bool
    {
        return in_array($api, self::PROCESSABLE_REQUESTS, true);
    }

    public function getToken($params)
    {
        $device = $params['device'];
        $customer = $params['customer'];
        $method = 'ReqListKeys';

        extract($this->getCommonVariables());

        // NPCI asks for these details
        assertTrue(strlen($customer['contact']) === 12);
        $data = $device['imei'] . "|" . $device['package_name'] . "|" . $customer['contact'] . "|" . $device['challenge'];
        // We send device.id in the notes to find the device in the response

        $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="{$device['id']}" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="GetToken"/>
<Creds>
<Cred type="challenge" subType="initial">
<Data code="NPCI" ki="20150822">$data</Data>
</Cred>
</Creds>
</upi:ReqListKeys>
EOT;

        $this->fireRequest($method, $txnId, $str);

        return ['txn_id' => $txnId, 'msg_id' => $msgId];
    }

    protected function preProcessRespListKeys($msgId, $request)
    {
        // TODO: Move this to constant inside Txn class?
        $txn = $request->getTxn();
        $type = $txn->getType();

        if ($type === 'GetToken')
        {
            $deviceId = $txn->getNote();
            $keys = $request->getKeyList();

            if (count($keys) === 1)
            {
                $key = $keys[0];

                return [
                    'token'         =>  $key->getKeyValue(),
                    'device_id'     =>  $deviceId,
                ];
            }
            // We return the token and other details
        }
        else if ($type === 'ListKeys')
        {
            return [
                'cacheKey'      =>  'UPI.ListKeys',
                // TODO
                'cacheValue'    =>  'THIS SHOULD HOLD PARSED LISTKEYS RESPONSE'
            ];
        }
    }

    protected function preProcessRespListAccPvd($msgId, $request)
    {
        return [
            'cacheKey'      =>  'UPI.RespListAccPvd',
            'cacheValue'    =>  json_encode($request->getAccPvdList()),
        ];
    }

    /**
     * Someone is asking us for bank accounts!
     *
     * @param $msgId
     * @param $request
     *
     * @return array
     */
    protected function preProcessReqListAccount($msgId, $request)
    {
        return [
            'mobile'    =>  $request->getLink()->getValue()
        ];
    }

    protected function preProcessReqRegMob($msgId, $request)
    {
        $creds = [];

        $account = $request->getPayer()->getAc();


        $details = $request->getRegDetails();
        $creds['last6'] = $details->getDetailByName('CARDDIGITS');
        $creds['expiry'] = $details->getDetailByName('EXPDATE');

        $creds['otp'] = $this->decrypt($details->getCredByTypeAndSubType('OTP', 'SMS'));
        $creds['mpin'] = $this->decrypt($details->getCredByTypeAndSubType('PIN', 'MPIN'));

        // $creds['otp'] = $details->getCredByTypeAndSubType('OTP', 'SMS');
        // $creds['mpin'] = $details->getCredByTypeAndSubType('PIN', 'MPIN');

        $creds['account'] = [
            'IFSC'  =>  $account->getDetailByName('IFSC'),
            'NUM'   =>  $account->getDetailByName('ACNUM')
        ];

        return $creds;
    }

    protected function preProcessReqSetCre($msgId, $request)
    {
        $creds = [];

        $account = $request->getPayer()->getAc();


        $details = $request->getRegDetails();
        $creds['last6'] = $details->getDetailByName('CARDDIGITS');
        $creds['expiry'] = $details->getDetailByName('EXPDATE');

        $creds['otp'] = $this->decrypt($details->getCredByTypeAndSubType('OTP', 'SMS'));
        $creds['mpin'] = $this->decrypt($details->getCredByTypeAndSubType('PIN', 'MPIN'));

        // $creds['otp'] = $details->getCredByTypeAndSubType('OTP', 'SMS');
        // $creds['mpin'] = $details->getCredByTypeAndSubType('PIN', 'MPIN');

        $creds['account'] = [
            'IFSC'  =>  $account->getDetailByName('IFSC'),
            'NUM'   =>  $account->getDetailByName('ACNUM')
        ];

        return $creds;
    }

    /**
     * This is the private key used for
     * decrypting responses we get from the
     * gateway server
     * @see getPublicKey
     * @return string Private Key
     */
    protected function getPrivateKey()
    {
        $key = $this->config['test_private_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }

    /**
     * Decrypts responses from the ICICI API
     * @param  string $data
     * @return string
     */
    public function decrypt($data)
    {
        $data = base64_decode($data);

        $rsa = $this->getRSAInstance();

        $key = $this->getPrivateKey();

        $rsa->loadKey($key);

        return $rsa->decrypt($data);
    }

    protected function getRSAInstance()
    {
        /**
         * We need to do this to use PCCS 1.5 instead of 1.7
         * which is the default. This is because of what the
         * bank uses on the other side.
         */
        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false)
        {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
        }

        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * @param $requestData
     *   parsed_request: The full parsed request sent in UPI callback,
     *   api: The method/api for which the UPI callback is for,
     *   id: The transaction ID of the UPI callback,
     *   body: The raw form of the $parsedRequest
     *
     * @return array
     */
    public function handleRequest($requestData)
    {
        $parsedRequest = $requestData['parsed_request'];
        $api = $requestData['api'];

        $msgId = $parsedRequest->getHead()->getMsgId();

        $res['queue'] = false;

        if ($this->needsProcessing($api))
        {
            $params['original_request_params'] = [];
            $params['msgId'] = $msgId;
            $params['api'] = $api;

            $params = [
                'original_request_params'   => [],
                'msg_id'                    => $msgId,
                'api'                       => $api,
                'parsed_request'            => $parsedRequest,
            ];

            list($jobName, $data) = $this->getJobDetails($params);

            $res = [
                'queue' => true,
                'post_processed' => true,
                'job' => $jobName,
                'params' => $data,
            ];
        }

        return $res;
    }

    protected function getJobDetails($params)
    {
        $api = $params['api'];

        $method = "preProcess$api";

        $data = $this->$method($params['msg_id'], $params['parsed_request']);

        // The reply message will use reqMsgId
        $data['reqMsgId'] = $params['msg_id'];

        $name = $this->getJobName($api);

        return [$name, $data];
    }

    protected function getJobName($api)
    {
        $jobs = [
            'ReqListAccount'    =>  'RespListAccount',
            'ReqRegMob'         =>  'RespRegMob',
            'RespListKeys'      =>  'UpdateKeyStore',
            'RespListAccPvd'    =>  null
        ];

        return $jobs[$api];
    }

    public function generateAckResponse($ackData)
    {
        $api = $ackData['api'];
        $parsedRequest = $ackData['parsed_request'];

        $msgId = $parsedRequest->getHead()->getMsgId();

        $ts = upi_ts();

        $ackResponse = <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"><upi:Ack xmlns:upi="http://npci.org/upi/schema/" api="$api" reqMsgId="$msgId" ts="$ts"/>
EOT;

        return $ackResponse;
    }


    protected function makeUrl(string $method,string $txnId)
    {
        return "https://103.14.161.148/upi/$method/1.0/urn:txnid:$txnId";
    }

    protected function cacheRequestOrResponse(array $input, $msgId)
    {
        if (isset($input['params']['reqMsgId']))
        {
            $reqMsgId = $input['params']['reqMsgId'];
            Cache::forever("UPI.$reqMsgId.response", $input['params']);
        }
        else
        {
            Cache::forever("UPI.$msgId.request", $input);
        }
    }

    public function makeRequest(array $input)
    {
        $method = $input['method'];
        $params = $input['params'];

        extract($this->getCommonVariables());

        $this->cacheRequestOrResponse($input, $msgId);

        switch ($method) {

            case 'RespRegMob':
            $result = $params['success'];
            $reqMsgId = $params['reqMsgId'];
$str = <<<EOT
<upi:RespRegMob xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ReqRegMob" />
<Resp reqMsgId="$reqMsgId" result="$result"/>
</upi:RespRegMob>
EOT;
                break;
            case 'ReqManageVae':
$str = <<<EOT
<upi:ReqManageVae xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>

<VaeList>
<Vae op="ADD" seqNum="1" name="Razorpay" addr="pay@razor" logo="image" url="https://razorpay.com/images/logo-black.png"/>
</VaeList>
</upi:ReqManageVae>
EOT;
    break;
        case 'ReqPay':
            $str = <<<EOT
<upi:ReqPay
    xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
    <Meta>
        <Tag name="PAYREQSTART" value="$ts"/>
        <Tag name="PAYREQEND" value="2017-01-01T20:23:02+05:30"/>
    </Meta>
    <Txn custRef="111111114423" id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="COLLECT">
        <RiskScores>
            <Score provider="sp" type="TXNRISK" value="0"/>
        </RiskScores>
        <Rules>
            <Rule name="EXPIREAFTER" value="50"/>
            <Rule name="MINAMOUNT" value="0.00"/>
        </Rules>
    </Txn>
    <Payer addr="yv@razor" name="Some Person" seqNum="1" type="PERSON" code="0000">
        <Info>
            <Identity type="ACCOUNT" verifiedName="Some Person" />
            <Rating verifiedAddress="FALSE"/>
        </Info>
        <Amount value="100.00" curr="INR">
        <Split name="PURCHASE" value="100.00"/>
        </Amount>
    </Payer>
    <Payees>
        <Payee addr="test@razor" name="Test Account" seqNum="1" type="PERSON" code="0000">
            <Info>
                <Identity type="ACCOUNT" verifiedName="Test Account Razorpay" />
                <Rating verifiedAddress="FALSE"/>
            </Info>
            <Amount value="100.00" curr="INR">
                <Split name="PURCHASE" value="100.00"/>
            </Amount>
            <Device>
                <Tag name="MOBILE" value="918861670264"/>
                <Tag name="GEOCODE" value="12.9667,77.5667"/>
                <Tag name="LOCATION" value="Sarjapur Road, Bangalore, IN" />
                <Tag name="IP" value="182.74.201.50"/>
                <Tag name="TYPE" value="MOB"/>
                <Tag name="ID" value="869649022152494"/>
                <Tag name="OS" value="Android"/>
                <Tag name="APP" value="com.razorpay.sampleapp"/>
                <Tag name="CAPABILITY" value="5200000200010004000639292929292"/>
            </Device>
            <Ac addrType="ACCOUNT">
            <Detail name="IFSC" value="RAZR0123456"/>
            <Detail name="ACTYPE" value="SAVINGS"/>
            <Detail name="ACNUM" value="12312312312"/>
            </Ac>
        </Payee>
    </Payees>
</upi:ReqPay>
EOT;
    break;
            case 'ReqValAdd':
                $str = <<<EOT
<upi:ReqValAdd xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="SAY YES PLEASE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ValAdd" />
<Payer addr="pay@razor" name="Abhay Rana" seqNum="1" type="PERSON" code="0000">
<Info>
<Rating verifiedAddress="TRUE"/>
<Identity type="ACCOUNT" verifiedName="Abhay Rana" />
</Info>
</Payer>
<Payee seqNum="1" addr="nemotest@pockets" name="LIC"/>
</upi:ReqValAdd>
EOT;

                break;
            case 'ReqListVae':
                $str = <<<EOT
<upi:ReqListVae xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ListVae" />
</upi:ReqListVae>
EOT;
                break;
            case 'ReqHbt':
                $str = <<<EOT
<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="Hbt" />
<HbtMsg type="ALIVE" value="NA"/>
</upi:ReqHbt>
EOT;
                break;
            case 'ReqListPsp':
                $str = <<<EOT
<upi:ReqListPsp xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ListPsp"/>
</upi:ReqListPsp>
EOT;
                break;

            case 'ReqPendingMsg':
                $str = <<<EOT
<upi:ReqPendingMsg xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="PendingMsg" />
<ReqMsg type="MOBILE" value="919458113956" addr="nemo@razor" />
</upi:ReqPendingMsg>
EOT;
                break;

            case 'ReqListAccPvd':
                $str = <<<EOT
<upi:ReqListAccPvd xmlns:upi="http://npci.org/upi/schema/"><Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/><Txn id="$txnId" note="" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ListAccPvd"/></upi:ReqListAccPvd>
EOT;
                break;
            case 'ReqListAccount':

                $str = <<<EOT
<upi:ReqListAccount xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="RAZOR" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="http://www.npci.org.in/" ts="$ts" type="ListAccount" />
<Link type="MOBILE" value="919440002345"/>
<Payer addr="razorpay@razor" seqNum="1" type="PERSON" code="0000">
<Ac addrType="MOBILE">
<Detail name="MOBNUM" value="919440002345"/>
</Ac>
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
</Ac>
</Payer>
</upi:ReqListAccount>
EOT;
                break;

            case 'ReqSetCre':
                $input          = $params['input'];
                $customer       = $params['customer'];
                $device         = $params['device'];
                $bankAccount    = $params['bank_account'];

                // The txnId must be provided by the sdk in this case
                if (isset($input['txnId']))
                {
                    $txnId = $input['txnId'];
                }

                $str = <<<EOT
<upi:ReqSetCre xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="SetCre"/>
<Payer addr="{$customer['id']}@razor" name="Unknown" seqNum="1" type="PERSON" code="0000">
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="{$bankAccount['account_number']}"/>
</Ac>
<Creds>
    <Cred type="PIN" subType="MPIN">
        <Data>{$input['mpincredblock']}</Data>
    </Cred>
</Creds>
<NewCred>
    <Cred type="PIN" subType="MPIN">
        <Data>{$input['nmpincredblock']}</Data>
    </Cred>
</NewCred>
</Payer>
</upi:ReqSetCre>
EOT;
            break;

            case 'ReqRegMob':
                $input          = $params['input'];
                $customer       = $params['customer'];
                $device         = $params['device'];
                $bankAccount    = $params['bank_account'];

                // The txnId must be provided by the sdk in this case
                if (isset($input['txnId']))
                {
                    $txnId = $input['txnId'];
                }
            $str = <<<EOT
<upi:ReqRegMob xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ReqRegMob"/>
<Payer addr="{$customer['id']}@razor" name="Razorpay Customer" seqNum="1" type="PERSON" code="0000">
<Device>
<Tag name="MOBILE" value="{$customer['contact']}"/>
<Tag name="GEOCODE" value="12.9667,77.5667"/>
<Tag name="LOCATION" value="Sarjapur Road, Bangalore, IN" />
<Tag name="IP" value="182.74.201.50"/>
<Tag name="TYPE" value="MOB"/>
<Tag name="ID" value="{$device['imei']}"/>
<Tag name="OS" value="Android"/>
<Tag name="APP" value="{$device['package_name']}"/>
<Tag name="CAPABILITY" value="5200000200010004000639292929292"/>
</Device>
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR0000001"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="{$bankAccount['account_number']}"/>
</Ac>
</Payer>
<RegDetails type="FORMAT1">
<Detail name="MOBILE" value="{$customer['contact']}"/>
<Detail name="CARDDIGITS" value="{$input['last6']}"/>
<Detail name="EXPDATE" value="{$input['expiry']}"/>
<Creds>
<Cred type="OTP" subType="SMS">
<Data code="NPCI" ki="20150822">{$input['otpcredblock']}</Data>
</Cred>
<Cred type="PIN" subType="MPIN">
<Data code="NPCI" ki="20150822">{$input['mpincredblock']}</Data>
</Cred>
</Creds>
</RegDetails>
</upi:ReqRegMob>
EOT;
                break;

            case 'GetToken':
                $device = $params;
                $method = 'ReqListKeys';
                // NPCI asks for these details
                assertTrue(strlen($device['customer']['contact']) === 12);
                $data = $device['imei'] . "|" . $device['package_name'] . "|" . $device['customer']['contact'] . "|" . $device['challenge'];
                // We send device.id in the notes to find the device in the response

                $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="{$device['id']}" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="GetToken"/>
<Creds>
<Cred type="challenge" subType="initial">
<Data code="NPCI" ki="20150822">$data</Data>
</Cred>
</Creds>
</upi:ReqListKeys>
EOT;

            break;

        case 'ListKeys':
                $method = 'ReqListKeys';

                $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="GET" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ListKeys"/>
</upi:ReqListKeys>
EOT;
            break;

            default:
                throw new \Exception("Invalid Method");
                break;
        }

        $this->fireRequest($method, $txnId, $str);

        return ['txn_id' => $txnId, 'msg_id' => $msgId];
    }

    protected function signXml($xml)
    {
        $xml = str_replace("\n", "", $xml);
        chdir("/home/nemo/projects/work/razorpay/upi-clients/tmp");
        file_put_contents("/home/nemo/projects/work/razorpay/upi-clients/tmp/request.txt", $xml);
        @unlink('request.xml');
        shell_exec("java SignatureGen");

        return file_get_contents('request.xml');
    }

    protected function fireRequest(string $method, string $txnId, string $unsignedXml)
    {
        $url = $this->makeUrl($method, $txnId);

        $signedXml = $this->signXml($unsignedXml);

        $request = [
            'url'       =>  $url,
            'method'    =>  'POST',
            'headers'   => [
                "Content-Type"  => 'application/xml',
                "Accept"        => 'application/xml',
            ],
            'content'   =>  $signedXml,
            'options'   =>  [
                'verify'        =>  storage_path('certs/npci.pem'),
                'verifyname'    =>  false,
            ]
        ];

        $response = $this->sendGatewayRequest($request);

        /**
         * TODO: Ensure that any sensitive content
         * is stripped. (All credblocks are encrypted anyway)
         */
        Trace::info(
            TraceCode::GATEWAY_RESPONSE,
            [
                'request'   => $request,
                'AAAAAAAAAAAAAAAAAAAA'  => $response->body,
                'status'    => $response->status_code,
                'headers'   => $response->headers
            ]);

        return true;
    }
}
