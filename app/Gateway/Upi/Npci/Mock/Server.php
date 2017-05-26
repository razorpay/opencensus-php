<?php

namespace RZP\Gateway\Upi\Npci\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function upiRequest(string $method, array $input)
    {
        $array = $this->getArrayFromXml($input['content']);

        $ts = upi_ts();

        $str = $this->$method($array, $ts);

        $request = $this->getStandardRequestArray($str, $input['url']);

        $this->makeAsyncRequest($request);

        $authResponse = $this->getAuthResponse($method);

        return $this->makeUpiResponse($authResponse, $input['url']);
    }

    public function ReqHbt(array $array, string $ts)
    {
        $str = <<<EOT
<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="Hbt" />
    <HbtMsg type="ALIVE" value="NA"/>
</upi:ReqHbt>
EOT;

        return $str;
    }

    public function ReqListAccPvd(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespListAccPvd xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="ListAccPvd"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <AccPvdList>
        <AccPvd name="HDFC" iin="901345" ifsc="" active="Y" url="" spocName="Razorpay-Hdfc" spocEmail="" spocPhone="" prods="AEPS,IMPS,CARD,NFS" lastModifedTs=""/>
        <AccPvd name="ICICI" iin="901346" ifsc="" active="N" url="" spocName="Razorpay-Icici" spocEmail="" spocPhone="" prods="AEPS,IMPS,CARD,NFS" lastModifedTs=""/>
    </AccPvdList>
</upi:RespListAccPvd>
EOT;

        return $str;
    }

    public function ReqListPsp(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespListAccPvd xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="ListAccPvd"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <PspList>
        <Psp name="HDFC" codes="hdfcgold,hdfcsliver" active="Y" url="" spocName="" spocEmail="" spocPhone="" lastModifedTs=""/>
        <Psp name="ICICI" codes="icici,iciciwallet" active="N" url="" spocName="" spocEmail="" spocPhone="" lastModifedTs=""/>
       </PspList>
</upi:RespListAccPvd>
EOT;

        return $str;
    }

    public function ReqListKeys(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespListKeys xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="{$array['Txn']['@attributes']['type']}"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <keyList>
        <key code="NPCI" type="PKI" ki="201705">
            <keyValue>Token</keyValue>
        </key>
        <key code="NPCI" type="CLF" ki="201705">
            <keyValue>Token</keyValue>
        </key>
    </keyList>
</upi:RespListKeys>
EOT;

        return $str;
    }

    public function ReqListAccount(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespListAccount xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="{$array['Txn']['@attributes']['type']}"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <AccountList>
        <Account accType="SAVINGS" mbeba="" accRefNumber="" maskedAccnumber="" ifsc="HDFC0000101" mmid="9056014" name="" aeba="Y">
            <CredsAllowed type="AADHAAR" subType="OTP" dType="" dLength=""/>
        </Account>
        <Account accType="CURRENT" mbeba="" accRefNumber="" maskedAccnumber="" ifsc="HDFC0000103" mmid="9056114" name="" aeba="N">
            <CredsAllowed type="PIN" subType="MPIN" dType="" dLength=""/>
        </Account>
    </AccountList>
</upi:RespListAccount>
EOT;

        return $str;
    }

    public function ReqListVae(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespListVae xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="{$array['Txn']['@attributes']['type']}"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
        <VaeList>
            <Vae name="LIC" addr="lic@hdfc" logo="image" url=""/>
            <Vae name="IRCTC" addr="irctc@icici" logo="image" url=""/>
        </VaeList>
</upi:RespListVae>
EOT;

        return $str;
    }

    public function ReqManageVae(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespManageVae xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="{$array['Txn']['@attributes']['type']}"/>
    <Resp reqMsgId="" result="SUCCESS" errCode="">
        <Ref op="" seqNum="1" addr="" result="SUCCESS" respCode=""/>
        <Ref op="" seqNum="2" addr="" result="SUCCESS" respCode=""/>
    </Resp>
</upi:RespManageVae>
EOT;

        return $str;
    }

    public function ReqValAdd(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespValAdd xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="{$array['Txn']['@attributes']['type']}"/>
    <Resp reqMsgId="" result="SUCCESS" errCode="" maskName="" />
</upi:RespValAdd>
EOT;

        return $str;
    }

    public function ReqRegMob(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespRegMob xmlns:upi="http://npci.org/upi/schema/">
  <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
  <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="ReqRegMob" />
  <Resp reqMsgId="" result="SUCCESS" errCode=""/>
</upi:RespRegMob>
EOT;

        return $str;
    }

    public function ReqSetCre(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespSetCre xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="SetCre"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
</upi:RespSetCre>
EOT;

        return $str;
    }

    protected function getArrayFromXml(string $str)
    {
        $xml = simplexml_load_string($str);

        return json_decode(json_encode($xml), true);
    }

    protected function getAuthResponse(string $function) : string
    {
        $ts = upi_ts();

        $str = <<<EOT
<upi:Ack xmlns:upi="" api="$function" reqMsgId="" err="" ts="$ts"/>
EOT;

        return $str;
    }

    protected function getStandardRequestArray(string $str, string $url)
    {
        $request = [
            'content' => $str,
            'url'     => $url,
            'method'  => 'post',
            'headers' => [
                'Content-Type' => 'application/xml'
            ]
        ];

        return $request;
    }
}
