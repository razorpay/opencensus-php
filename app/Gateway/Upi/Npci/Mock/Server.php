<?php

namespace RZP\Gateway\Upi\Npci\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;

class Server extends Base\Mock\Server
{
    public function ReqHbt(array $input)
    {
        $xml = simplexml_load_string($input['content']);

        $array = json_decode(json_encode($xml), true);

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
}
