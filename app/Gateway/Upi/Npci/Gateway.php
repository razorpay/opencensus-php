<?php

namespace RZP\Gateway\Upi\Npci;

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
    const KEY_VALUE = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4rIIEHkJ2TYgO/JUJQI/sxDgbDEAIuy9uTf4DItWeIMsG9AuilOj9R+dwAv8S6/9No/z0cwsw4UnsHQG1ALVIxFznLizMjaVJ7TJ+yTS9C9bYEFakRqH8b4jje7SC7rZ9/DtZGsaWaCaDTyuZ9dMHrgcmJjeklRKxl4YVmQJpzYLrK4zOpyY+lNPBqs+aiwJa53ZogcUGBhx/nIXfDDvVOtKzNb/08U7dZuXoiY0/McQ7xEiFcEtMpEJw5EB4o3RhE9j/IQOvc7l/BfD85+YQ5rJGk4HUb6GrQXHzfHvIOf53l1Yb0IX4v9q7HiAyOdggO+PVzXMSbrcFBrEjGZD7QIDAQAB";

    public function __construct()
    {
    }

    protected function makeUrl($method, $txnId)
    {
        return "https://103.14.161.148/upi/$method/1.0/urn:txnid:$txnId";
    }

    public function makeRequest($method, $params)
    {
        $txnId = upi_uuid();
        $ids = [upi_uuid(), upi_uuid(), upi_uuid()];
        $ts = upi_ts();

        $refUrl = "http://www.npci.org.in/";
        $orgId = 'RAZOR';

        switch ($method) {
            case 'ReqHbt':
                $str = <<<EOT
<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="$orgId" msgId="$ids[0]"/>
<Txn id="$txnId" note="HELLO WORLD" refId="{$ids[1]}" refUrl="$refUrl" ts="$ts" type="Hbt" />
<HbtMsg type="ALIVE" value="NA"/>
</upi:ReqHbt>
EOT;
                break;
            case 'ReqListPsp':
                $str = <<<EOT
<upi:ReqListPsp xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$ids[0]}"/>
<Txn id="$txnId" note="" refId="{$ids[1]}" refUrl="$refUrl" ts="$ts" type="ListPsp"/>
</upi:ReqListPsp>
EOT;
                break;

            case 'ReqListAccPvd':
                $str = <<<EOT
<upi:ReqListAccPvd xmlns:upi="http://npci.org/upi/schema/"><Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$ids[0]}"/><Txn id="$txnId" note="" refId="{$ids[1]}" refUrl="$refUrl" ts="$ts" type="ListAccPvd"/></upi:ReqListAccPvd>
EOT;
                break;

            case 'GetToken':
                $method = 'ReqListKeys';
                $data = \Request::get('query', null);

                $data = $data ?? '869649022152494|com.razorpay.sampleapp|918861670264|nB5ssiRTpG+5VDsPdtTaBipbfnLNX6S7arzVD8Mrm/tQHn3BWziEqSOCBb1sUKsdTUjyZNXHyoSNEbg1P2BSMkPMMYR8u+5ztsRD9+OakUrd4nyDupGfy72JbP9yE67RpD4ZpNWoyTn1Er6/G2MaA8nX+zJ84VT5TDFS88o2oEPtDWKKaQr+0AY5PioReTPdnBKiNBJftknwQe0HFWLpYXhmvlqK0w64NyeZyZlAoI/50qrwAL6+olewb2TZ0XFhcQaY2JdgOlF6auIAk5t5Xh5Q+Rwhrhry7gggw0wVExUgjBkJo+vgtk/yVwd1t/VxKKEQ9JVzBh3tKGJTHJ6pSA==';

                $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$ids[0]}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[1]}" refUrl="$refUrl" ts="$ts" type="GetToken"/>
<Creds>
<Cred type="challenge" subType="initial">
<data code="NPCI" ki="20150822">$data</data>
</Cred>
</Creds>
</upi:ReqListKeys>
EOT;

            break;

        case 'ListKeys':
                $method = 'ReqListKeys';

                $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$ids[0]}"/>
<Txn id="$txnId" note="GET" refId="{$ids[1]}" refUrl="$refUrl" ts="$ts" type="ListKeys"/>
</upi:ReqListKeys>
EOT;
            break;

            default:
                break;
        }

        $this->fireRequest($method, $txnId, $str);

        return $txnId;
    }

    protected function signXml($xml)
    {
        $xml = str_replace("\n", "", $xml);
        chdir("/home/nemo/projects/work/razorpay/upi-clients/tmp");
        file_put_contents("/home/nemo/projects/work/razorpay/upi-clients/tmp/request.txt", $xml);
        unlink('request.xml');
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
                "Accept"        =>'application/xml',
            ],
            'content'   =>  $signedXml,
            'options'   =>  [
                'verify'    =>  false//storage_path('certs/npci.pem')
            ]
        ];

        $response = $this->sendGatewayRequest($request);

        Trace::info(
            TraceCode::GATEWAY_PAYMENT_RESPONSE,
            [
                'request'   => $request,
                'response'  => $response->body,
                'status'    => $response->status_code,
                'headers'   => $response->headers
            ]);

        return true;
    }
}
