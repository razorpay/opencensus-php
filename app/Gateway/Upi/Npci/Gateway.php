<?php

namespace RZP\Gateway\Upi\Npci;

use Cache;
use Carbon\Carbon;
use RZP\Gateway\Upi\Base;
use ErrorException;
use RZP\Trace\TraceCode;
use Trace;
use RZP\Http\Request\Requests;
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
        // Translate some addresses
        'ReqAuthDetails',
        // This would usually mean check the balance and return to the user
        // but we are piggy-banking this for Payment Authorizations
        'ReqBalEnq',
    ];

    protected $gateway = 'upi_npci';

    public function __construct()
    {
        parent::__construct();

        $this->crypto = new Crypto($this->config);
    }

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
        $subType = $params['challengeType'];
        $method = 'ReqListKeys';

        $commonVars = $this->getCommonVariables();
        $txnId      = $commonVars['txnId'];
        $ts         = $commonVars['ts'];
        $orgId      = $commonVars['orgId'];
        $msgId      = $commonVars['msgId'];
        $ids        = $commonVars['ids'];
        $refUrl     = $commonVars['refUrl'];

        // NPCI asks for these details
        assertTrue(strlen($customer['contact']) === 12);
        $data = $device['imei'] . "|" . $device['package_name'] . "|" . $customer['contact'] . "|" . $device['challenge'];
        // We send device.id in the notes to find the device in the response

        $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="{$device['id']}" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="GetToken"/>
<Creds>
<Cred type="challenge" subType="$subType">
<Data code="NPCI" ki="20150822">$data</Data>
</Cred>
</Creds>
</upi:ReqListKeys>
EOT;

        $this->fireRequest($method, $txnId, $str);

        return ['txn_id' => $txnId, 'msg_id' => $msgId];
    }

    /**
     * Note: We are piggy banking this request as the authorization
     * webhook for now
     */
    protected function preProcessReqBalEnq($msgId, $request)
    {
        $txn = $request->getTxn();
        $p2pId = $txn->getNote();

        $cred = $request->getPayer()->getCreds()[0];
        $mpin = $this->crypto->decrypt($cred->getData()->value());

        return [
            'p2p_id'    =>  $txn->getNote(),
            'mpin'      =>  $mpin
        ];
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
                    'token'         => $key->getKeyValue(),
                    'device_id'     => $deviceId,
                ];
            }
            // We return the token and other details
        }
        else if ($type === 'ListKeys')
        {
            return [
                'cacheKey'      => 'UPI.ListKeys',
                // TODO
                'cacheValue'    => 'THIS SHOULD HOLD PARSED LISTKEYS RESPONSE'
            ];
        }
    }

    protected function preProcessRespListAccPvd($msgId, $request)
    {
        return [
            'cacheKey'      => 'UPI.RespListAccPvd',
            'cacheValue'    => json_encode($request->getAccPvdList()),
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
            'mobile'    => $request->getLink()->getValue()
        ];
    }

    protected function preProcessReqRegMob($msgId, $request)
    {
        $creds = [
            'txnId' =>  $request->getTxn()->getId()
        ];

        $account = $request->getPayer()->getAc();

        $details = $request->getRegDetails();
        $creds['last6'] = $details->getDetailByName('CARDDIGITS');
        $creds['expiry'] = $details->getDetailByName('EXPDATE');

        $creds['otp'] = $this->crypto->decrypt($details->getCredByTypeAndSubType('OTP', 'SMS'));
        $creds['mpin'] = $this->crypto->decrypt($details->getCredByTypeAndSubType('PIN', 'MPIN'));

        // $creds['otp'] = $details->getCredByTypeAndSubType('OTP', 'SMS');
        // $creds['mpin'] = $details->getCredByTypeAndSubType('PIN', 'MPIN');

        $creds['account'] = [
            'IFSC'  =>  $account->getDetailByName('IFSC'),
            'NUM'   =>  $account->getDetailByName('ACNUM')
        ];

        return $creds;
    }


    protected function preProcessReqAuthDetails($msgId, $request)
    {
        $payee = $request->getPayees()[0];
        $payer = $request->getPayer();
        $identity = new \Razorpay\UPI\IdentityType;

        $identity->setType('ACCOUNT');
        $identity->setVerifiedName('Hari Ram');

        $info = new \Razorpay\UPI\InfoType;
        $info->setIdentity($identity);

        $ac = new \Razorpay\UPI\AccountType;

        $ac->setAddrType('ACCOUNT');

        $detail = new \Razorpay\UPI\AccountType\DetailAType;
        $detail->setName('IFSC');
        $detail->setValue('RAZR0000001');
        $ac->addToDetail($detail);

        $detail = new \Razorpay\UPI\AccountType\DetailAType;
        $detail->setName('ACTYPE');
        $detail->setValue('SAVINGS');
        $ac->addToDetail($detail);

        $detail = new \Razorpay\UPI\AccountType\DetailAType;
        $detail->setName('ACNUM');
        // TODO: Set this in core somehow later
        // in the post processing
        $detail->setValue('12345');
        $ac->addToDetail($detail);

        $payee->setAc($ac);

        $request->setPayees([$payee]);

        return [
            'payee' => $payee,
            'payer' => $payer,
            'txn'   => $request->getTxn()
        ];
    }

    protected function preProcessReqSetCre($msgId, $request)
    {
        $creds = [
            'txnId' =>  $request->getTxn()->getId()
        ];

        $account = $request->getPayer()->getAc();

        $creds['mpin'] = $this->crypto->decrypt($request->getPayer()->getCreds()[0]->getData()->value());
        $creds['nmpin'] = $this->crypto->decrypt(($request->getPayer()->getNewCred()[0]->getData()->value()));

        $creds['account'] = [
            'IFSC'  => $account->getDetailByName('IFSC'),
            'NUM'   => $account->getDetailByName('ACNUM')
        ];

        return $creds;
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
            'ReqListAccount'    => 'RespListAccount',
            'ReqRegMob'         => 'RespRegMob',
            'RespListKeys'      => 'UpdateKeyStore',
            'RespListAccPvd'    => null,
            'ReqAuthDetails'    => 'RespAuthDetails',
            'ReqBalEnq'         => 'AuthorizePayment',
            'ReqSetCre'         => 'RespSetCre'
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
<?xml version="1.0" encoding="UTF-8" standalone="yes">
<upi:Ack xmlns:upi="http://npci.org/upi/schema/" api="$api" reqMsgId="$msgId" ts="$ts"/>
EOT;

        return $ackResponse;
    }


    protected function makeUrl(string $method,string $txnId)
    {
        return "http://npci.razorpay.in/upi/$method/1.0/urn:txnid:$txnId";
    }

    protected function cacheRequestOrResponse(array $input, $msgId, string $txnId)
    {
        if (isset($input['params']['reqMsgId']))
        {
            $reqMsgId = $input['params']['reqMsgId'];

            $key = "UPI.$reqMsgId.response";
            Cache::forever($key, $input['params']);
        }
        else
        {
            $key = "UPI.$txnId.request";
            Cache::forever($key, $input);
        }
    }

    public function makeRequest(array $input)
    {
        $method = $input['method'];
        $params = $input['params'];

        $commonVars = $this->getCommonVariables();
        $txnId      = $commonVars['txnId'];
        $ts         = $commonVars['ts'];
        $orgId      = $commonVars['orgId'];
        $msgId      = $commonVars['msgId'];
        $ids        = $commonVars['ids'];
        $refUrl     = $commonVars['refUrl'];

        $this->cacheRequestOrResponse($input, $msgId, $txnId);

        switch ($method)
        {
            case 'RespAuthDetails':
                $payee = $params['payee'];
                $payer = $params['payer'];
                $txn = $params['txn'];

                $reqMsgId = $params['reqMsgId'];
                $str = <<<EOT
<upi:RespAuthDetails xmlns:upi="http://npci.org/upi/schema/">
<Txn id="$txnId" note="{$txn->getNote()}" refId="{$txn->getRefId()}" custRef="{$txn->getCustRef()}" refUrl="{$txn->getRefUrl()}" ts="{$txn->getTs()}" type="{$txn->getType()}" />
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
<Resp reqMsgId="$reqMsgId" result="SUCCESS" />
<Payer addr="nemo@razor" code="0000" name="Hari Ram" seqNum="1" type="PERSON">
    <Info>
        <Identity type="ACCOUNT" verifiedName="Hari Ram"/>
    </Info>
    <Ac addrType="ACCOUNT" name="Hari Ram">
        <Detail name="IFSC" value="RAZR0000001"/>
        <Detail name="ACTYPE" value="SAVINGS"/>
        <Detail name="ACNUM" value="1234"/>
    </Ac>
    <Device>
        <Tag name="MOBILE" value="919639516176"/>
        <Tag name="GEOCODE" value="12.9667,77.5667"/>
        <Tag name="LOCATION" value="Sarjapur Road, Bangalore, KA, IN"/>
        <Tag name="IP" value="1.2.3.4"/>
        <Tag name="ID" value="358960060336586"/>
        <Tag name="OS" value="Android 5.3"/>
        <Tag name="APP" value="com.razorpay.upi.sampleapp"/>
        <Tag name="CAPABILITY" value="011001"/>
    </Device>
    <Amount curr="INR" value="500.00"/>
    <Creds>
        <Cred subType="MPIN" type="PIN">
            <Data code="NPCI" ki="20150822">2.0|pckK/mhE5zQsim0TYpKZVnAV6Ixxfz7L/zX9GfmhVLI2Lg09OFAFov4SBIVxnmOCBblqVfJi7hMPNzEvgc3mIOM8SnEaB30iKIj1oTGfFFyINE0g5ZNwLkRY96o54Qnt8hgYpfOrJ26wQnaFk95K0UThTmXP9AjqSUTf26bx9FndU8msRLpZBjJxTKt6MGw7o05qKSkb1cDwTlzG9eJwLnLBHS3B5rCgp1JM1jZ2BPqSiZZYd+gDQ9UEueh7A+OALJWMwgM1JhLqSUfz89E6SueQVLsa1zv2tubKSUdSvHZieenc1k6V0g9342h70G44idySm76jF5laHYTcFfokOQ==</Data>
        </Cred>
    </Creds>
</Payer>
<Payees>
    <Payee addr="{$payee->getAddr()}" name="Hari Ram" seqNum="1" type="PERSON">
        <Ac addrType="{$payee->getAc()->getAddrType()}">
            <Detail name="IFSC" value="RAZR0000001"/>
            <Detail name="ACTYPE" value="SAVINGS"/>
            <Detail name="ACNUM" value="12345"/>
        </Ac>
        <Amount value="500.00" curr="INR"/>
    </Payee>
</Payees>
</upi:RespAuthDetails>
EOT;
            break;

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
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ManageVae" />
<VaeList>
<Vae op="ADD" seqNum="1" name="Razorpay" addr="pay@razor" logo="image" url="https://razorpay.com/images/logo-black.png"/>
</VaeList>
</upi:ReqManageVae>
EOT;
    break;
        // TODO: Switch to this when you want to try out real payments
        case 'ReqPay':
            $mpinCredBlock = $input['params']['gateway']['mpincredblock'];
            $imei = $input['params']['device']->getImei();
            $packageName = $input['params']['device']->getPackageName();
            $amount = number_format(/*$input['params']['p2p']->getAmount()*/ 100, 2, '.', '');
            $source = $input['params']['p2p']->source;
            $sink = $input['params']['p2p']->sink;
            $customer = $input['params']['p2p']->customer;

            $txnId = $input['params']['p2p']->getTxnId(true);

            $str = <<<EOT
<upi:ReqPay xmlns:upi="http://npci.org/upi/schema/">
    <Head msgId="$msgId" orgId="$orgId" ts="$ts" ver="1.0"/>
    <Meta>
        <Tag name="PAYREQSTART" value="$ts"/>
    </Meta>
    <Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="PAY" custRef="111222233334">
    <RiskScores/>
    </Txn>
    <Payer addr="{$source->getAddress()}" name="Hari Ram" seqNum="1" type="PERSON">
        <Info>
            <Identity type="ACCOUNT" verifiedName="Hari Ram"/>
        </Info>
        <Ac addrType="ACCOUNT" name="Hari Ram">
            <Detail name="IFSC" value="RAZR0000001"/>
            <Detail name="ACTYPE" value="SAVINGS"/>
            <Detail name="ACNUM" value="{$source->bankAccount->getAccountNumber()}"/>
        </Ac>
        <Device>
            <Tag name="MOBILE" value="{$customer->getContact()}"/>
            <Tag name="GEOCODE" value="12.9667,77.5667"/>
            <Tag name="LOCATION" value="Sarjapur Road, Bangalore, KA, IN"/>
            <Tag name="IP" value="1.2.3.4"/>
            <Tag name="ID" value="{$imei}"/>
            <Tag name="OS" value="Android 5.3"/>
            <Tag name="APP" value="{$packageName}"/>
            <Tag name="CAPABILITY" value="011001"/>
        </Device>
        <Creds>
            <Cred subType="MPIN" type="PIN">
                <Data code="NPCI" ki="20150822">{$mpinCredBlock}</Data>
            </Cred>
        </Creds>
        <Amount curr="INR" value="{$amount}"/>
    </Payer>
    <Payees>
        <Payee name="Hari" seqNum="2" type="PERSON" addr="{$sink->getAddress()}">
        <Amount curr="INR" value="{$amount}"/>
    </Payee>
    </Payees>
</upi:ReqPay>
EOT;
    break;
            // We are currently sending a fake reqbalenq
            // for a payment request
            case 'ReqBalEnq':
                $params = $input['params'];
                $mpinCredBlock  = $params['gateway']['mpincredblock'];
                $imei = $params['device']['imei'];
                $packageName = $params['device']['package_name'];
                $customer = $params['customer'];
                $bankAccount = $params['bank_account'];

                $method = 'ReqBalEnq';

                $str = <<<EOT
<upi:ReqBalEnq xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="BalEnq">
<RiskScores/>
</Txn>
<Payer addr="nemo@razor" name="Hari Ram" seqNum="1" type="PERSON">
<Info>
<Identity type="ACCOUNT" verifiedName="Nemo" />
<Rating verifiedAddress="TRUE"/>
</Info>
<Device>
<Tag name="MOBILE" value="{$customer['contact']}"/>
<Tag name="GEOCODE" value="12.9667,77.5667"/>
<Tag name="LOCATION" value="Sarjapur Road, Bangalore, KA, IN"/>
<Tag name="IP" value="1.2.3.4"/>
<Tag name="ID" value="{$imei}"/>
<Tag name="OS" value="Android 5.3"/>
<Tag name="APP" value="{$packageName}"/>
<Tag name="CAPABILITY" value="011001"/>
</Device>
<Ac addrType="ACCOUNT" name="Hari Ram">
<Detail name="IFSC" value="RAZR0000001"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="1234"/>
</Ac>
<Creds>
<Cred type="PIN" subType="MPIN">
<Data code="NPCI" ki="20150822">$mpinCredBlock</Data>
</Cred>
</Creds>
</Payer>
</upi:ReqBalEnq>
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
                $txnId = $input['txnId'];

                $str = <<<EOT
<upi:ReqSetCre xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="SetCre"/>
<Payer addr="reserved@razor" name="Unknown" seqNum="1" type="PERSON" code="0000">
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="{$bankAccount['account_number']}"/>
</Ac>
<Creds>
    <Cred type="PIN" subType="MPIN">
        <Data code="NPCI" ki="20150822">{$input['mpincredblock']}</Data>
    </Cred>
</Creds>
<NewCred>
    <Cred type="PIN" subType="MPIN">
        <Data code="NPCI" ki="20150822">{$input['nmpincredblock']}</Data>
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
                $txnId = $input['txnId'];

            $str = <<<EOT
<upi:ReqRegMob xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ReqRegMob"/>
<Payer addr="reserved@razor" name="Razorpay Customer" seqNum="1" type="PERSON" code="0000">
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

        case 'ReqOtp':
                $params = $input['params'];
                $mpinCredBlock  = $params['input']['mpincredblock'];
                $imei = $params['device']['imei'];
                $packageName = $params['device']['package_name'];
                $customer = $params['customer'];
                $bankAccount = $params['bank_account'];

                $method = 'ReqOtp';

                $str = <<<EOT
<upi:ReqOtp xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="$msgId"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="Otp" />
<Payer addr="mayank@razor" name="" seqNum="" type="PERSON" code="">
    <Device>
        <Tag name="MOBILE" value="{$customer['contact']}"/>
        <Tag name="GEOCODE" value="12.9667,77.5667"/>
        <Tag name="LOCATION" value="Sarjapur Road, Bangalore, IN" />
        <Tag name="IP" value="182.74.201.50"/>
        <Tag name="TYPE" value="MOB"/>
        <Tag name="ID" value="{$imei}"/>
        <Tag name="OS" value="Android"/>
        <Tag name="APP" value="{$packageName}"/>
        <Tag name="CAPABILITY" value="5200000200010004000639292929292"/>
    </Device>
    <Ac addrType="ACCOUNT">
        <Detail name="IFSC" value="RAZR0000001"/>
        <Detail name="ACTYPE" value="SAVINGS"/>
        <Detail name="ACNUM" value="{$bankAccount['account_number']}"/>
    </Ac>
</Payer>
</upi:ReqOtp>
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

        return $this->crypto->sign($xml);
    }

    protected function fireRequest(string $method, string $txnId, string $unsignedXml)
    {
        $url = $this->makeUrl($method, $txnId);

        $signedXml = $this->signXml($unsignedXml);

        $request = [
            'url'       => $url,
            'method'    => 'POST',
            'headers'   => [
                "Content-Type"  => 'application/xml',
                "Accept"        => 'application/xml',
            ],
            'content'   => $signedXml,
            'options'   => [
                'verify'        => storage_path('certs/npci.pem'),
                'verifyname'    => false,
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
