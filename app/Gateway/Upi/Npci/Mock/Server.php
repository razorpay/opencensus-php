<?php

namespace RZP\Gateway\Upi\Npci\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function ReqHbt(array $input)
    {
        $array = $this->getArrayFromXml($input['content']);

        $ts = upi_ts();

        $str = <<<EOT
<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="HELLO WORLD" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts="$ts" type="Hbt" />
    <HbtMsg type="ALIVE" value="NA"/>
</upi:ReqHbt>
EOT;

        return $this->makeUpiResponse($str, $input['url']);
    }

    public function ReqListAccPvd(array $input)
    {
        $array = $this->getArrayFromXml($input['content']);

        $ts = upi_ts();

        $str = <<<EOT
<upi:RespListAccPvd xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts=$ts"" type="ListAccPvd"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <AccPvdList>
        <AccPvd name="HDFC" iin="901345" ifsc="" active="Y" url="" spocName="Razorpay-Hdfc" spocEmail="" spocPhone="" prods="AEPS,IMPS,CARD,NFS" lastModifedTs=""/>
        <AccPvd name="ICICI" iin="901346" ifsc="" active="N" url="" spocName="Razorpay-Icici" spocEmail="" spocPhone="" prods="AEPS,IMPS,CARD,NFS" lastModifedTs=""/>
    </AccPvdList>
</upi:RespListAccPvd>
EOT;

        return $this->makeUpiResponse($str, $input['url']);
    }

    public function ReqListPsp(array $input)
    {
        $array = $this->getArrayFromXml($input['content']);

        $ts = upi_ts();

        $str = <<<EOT
<upi:RespListAccPvd xmlns:upi="http://npci.org/upi/schema/">
    <Head ver="1.0" ts="$ts" orgId="{$array['Head']['@attributes']['orgId']}" msgId="{$array['Head']['@attributes']['msgId']}"/>
    <Txn id="{$array['Txn']['@attributes']['id']}" note="" refId="{$array['Txn']['@attributes']['refId']}" refUrl="{$array['Head']['@attributes']['orgId']}" ts=$ts"" type="ListAccPvd"/>
    <Resp reqMsgId="" result="SUCCESS" errCode=""/>
    <PspList>
        <Psp name="HDFC" codes="hdfcgold,hdfcsliver" active="Y" url="" spocName="" spocEmail="" spocPhone="" lastModifedTs=""/>
        <Psp name="ICICI" codes="icici,iciciwallet" active="N" url="" spocName="" spocEmail="" spocPhone="" lastModifedTs=""/>
       </PspList>
</upi:RespListAccPvd>
EOT;

        return $this->makeUpiResponse($str, $input['url']);
    }

    protected function getArrayFromXml(string $str)
    {
        $xml = simplexml_load_string($str);

        return json_decode(json_encode($xml), true);
    }
}
