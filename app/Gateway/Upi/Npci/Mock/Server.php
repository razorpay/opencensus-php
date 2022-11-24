<?php

namespace RZP\Gateway\Upi\Npci\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function upiRequest(string $reqMethod, array $input)
    {
        $array = $this->getArrayFromXml($input['content']);

        $ts = upi_ts();

        $method = $this->getRespMethod($reqMethod);

        if ($method === 'RespPay')
        {
            $str = $this->RespPay($array, $ts, $input['url']);
        }
        else
        {
            $str = $this->$method($array, $ts);
        }

        $request = $this->getStandardRequestArray($str, $input['url']);

        $this->makeAsyncRequest($request);

        $authResponse = $this->getAuthResponse($method);

        return $this->makeUpiResponse($authResponse, $input['url']);
    }

    public function RespHbt(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespHbt xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="Hbt" />
    <HbtMsg type="ALIVE" value="NA"/>
</upi:RespHbt>
EOT;

        return $str;
    }

    public function RespPay(array $array, string $ts, string $url)
    {
        $this->ReqAuthDetails($array, $ts, $url);

        $this->ReqTxnConfirmation($array, $ts, $url);

        $str = <<<EOT
<upi:RespPay xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
<Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="PAY" orgTxnId="" />
        <RiskScores>
            <Score provider="sp" type="TXNRISK" value=""/>
            <Score provider="npci" type="TXNRISK" value=""/>
        </RiskScores>
<Resp reqMsgId="" result="SUCCESS" errCode="">
    <Ref type="PAYER" seqNum="" addr="" regName="" settAmount="" settCurrency="" acNum ="" approvalNum="" respCode="" reversalRespCode="" />
    <Ref type="PAYEE" seqNum="" addr="" settAmount="" acNum ="" regName="" approvalNum="" respCode="" reversalRespCode="" />
</Resp>
</upi:RespPay>
EOT;

        return $str;
    }

    public function ReqAuthDetails(array $array, string $ts, string $url)
    {
        $str = <<<EOT
<upi:ReqAuthDetails xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
<Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="Pay" />
    <RiskScores>
        <Score provider="sp" type="TXNRISK" value=""/>
        <Score provider="NPCI" type="TXNRISK" value=""/>
    </RiskScores>
<Payer addr="" name="" seqNum="" type="PERSON" code="">
    <Info>
        <Identity type="PAN" verifiedName="" />
        <Rating VerifiedAddress="TRUE"/>
    </Info>
<Amount value="" curr="INR">
    <Split name="PURCHASE" value=""/>
</Amount>
</Payer>
<Payees>
    <Payee seqNum="" addr="" name="">
        <Info>
            <Identity type="PAN" verifiedName=""/> <Rating VerifiedAddress="TRUE"/>
        </Info>
        <Amount value="" curr="INR">
            <Split name="PURCHASE" value=""/>
        </Amount>
    </Payee>
</Payees>
</upi:ReqAuthDetails>
EOT;

        $request = $this->getStandardRequestArray($str, $url);

        $this->makeAsyncRequest($request);
    }

    public function ReqTxnConfirmation(array $array, string $ts, string $url)
    {
        $str = <<<EOT
<upi:ReqTxnConfirmation xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
<Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="TxnConfirmation" />
<TxnConfirmation note="" orgStatus="SUCCESS" orgErrCode="" type="" orgTxnId="">
    <Ref type="PAYER" seqNum="" addr="" regName="" settAmount="" settCurrency="" approvalNum="" respCode="" orgAmount="" reversalRespCode=""/>
</TxnConfirmation>
</upi:ReqTxnConfirmation>
EOT;

        $request = $this->getStandardRequestArray($str, $url);

        $this->makeAsyncRequest($request);
    }

    public function RespListAccPvd(array $array, string $ts)
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

    public function RespListPsp(array $array, string $ts)
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

    public function RespListKeys(array $array, string $ts)
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

    public function RespListAccount(array $array, string $ts)
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

    public function RespListVae(array $array, string $ts)
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

    public function RespManageVae(array $array, string $ts)
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

    public function RespValAdd(array $array, string $ts)
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

    public function RespRegMob(array $array, string $ts)
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

    public function RespSetCre(array $array, string $ts)
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

    public function RespOtp(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespOtp xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="Otp"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
</upi:RespOtp>
EOT;

        return $str;
    }

    public function RespBalEnq(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespBalEnq xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
<Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="BalEnq"/>
<RiskScores>
    <Score provider="sp" type="TXNRISK" value=""/>
    <Score provider="NPCI" type="TXNRISK" value=""/>
</RiskScores>
</Txn>
<Payer addr="" name="" seqNum="" type="PERSON" code="">
     <Bal>
            <Data>342342343</Data>
     </Bal>
</Payer>
</upi:RespBalEnq>
EOT;

        return $str;
    }

    public function RespPendingMsg(array $array, string $ts)
    {
        $str = <<<EOT
<upi:RespPendingMsg xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
<Txn id="{$array['Txn']['@attributes']['id']}" note="{$array['Txn']['@attributes']['note']}" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Txn']['@attributes']['refUrl']}" ts="$ts" type="PendingMsg"/>
<Resp reqMsgId=" " result="SUCCESS" errCode=""/>
<RespMsg>
    <PenTxn id=" " note="" refId="" refUrl="" ts="" type="COLLECT" orgTxnId=""/>
    <PenTxn id=" " note="" refId="" refUrl="" ts="" type="COLLECT" orgTxnId=""/>
</RespMsg>
</upi:RespPendingMsg>
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

    protected function getRespMethod(string $reqMethod) : string
    {
        return str_replace('Req', 'Resp', $reqMethod);
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

    protected function makeAsyncRequest($request)
    {
        $method = $request['method'];

        $response = Requests::request(
            $request['method'],
            $request['url'],
            $request['headers'],
            $request['content']
        );

        return $response;
    }

    protected function makeUpiResponse(string $xml, string $url)
    {
        $response = new \WpOrg\Requests\Response();

        $response->url = $url;
        $response->headers = ['Content-Type' => 'application/xml'];
        $response->body = $xml;
        $response->status_code = 200;
        $response->success = true;

        return $response;
    }
}
