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
    const NPCI_KEY_VALUE = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4rIIEHkJ2TYgO/JUJQI/sxDgbDEAIuy9uTf4DItWeIMsG9AuilOj9R+dwAv8S6/9No/z0cwsw4UnsHQG1ALVIxFznLizMjaVJ7TJ+yTS9C9bYEFakRqH8b4jje7SC7rZ9/DtZGsaWaCaDTyuZ9dMHrgcmJjeklRKxl4YVmQJpzYLrK4zOpyY+lNPBqs+aiwJa53ZogcUGBhx/nIXfDDvVOtKzNb/08U7dZuXoiY0/McQ7xEiFcEtMpEJw5EB4o3RhE9j/IQOvc7l/BfD85+YQ5rJGk4HUb6GrQXHzfHvIOf53l1Yb0IX4v9q7HiAyOdggO+PVzXMSbrcFBrEjGZD7QIDAQAB";

    const NPCI_KI = "20150822";

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
        $ids = [upi_uuid(), upi_uuid()];
        $ts = upi_ts();

        $msgId = upi_uuid();

        $refUrl = "http://www.npci.org.in/";
        $orgId = 'RAZOR';

        switch ($method) {
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
<Link type="MOBILE" value="918861670264"/>
<Payer addr="nemo@razor" name="Hari Ram" seqNum="1" type="PERSON" code="">
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
</Ac>
</Payer>
</upi:ReqListAccount>
EOT;
                break;

            case 'ReqSetCre':
                $str = <<<EOT
<upi:ReqSetCre xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="SetCre"/>
<Payer addr="hari@razor" name="Hari Ram" seqNum="1" type="PERSON" code="0000">
<Ac addrType="ACCOUNT">
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="8861670264"/>
</Ac>
<Creds>
    <Cred type="OTP" subType="SMS">
        <Data>2.0|wjUDl1U9zVyzXuE8uW3B3H8ldTatFxTXRsS6fmCtOkHUHaVsPBv9PxWgM0cs9c5Z2Vnbr6ByUzRuF0s3/vXre9WHripeygg7/FS8aXfWRf68LEYuy1biNuR4d8TTLjJb7SUqg8nDp856sZpWgex51FqG6VHQ3OSyb0AQHQ/REJcm0KMWXAcaCTllf32yKrgYVnU0rVS9HerTq5nv9ar7ERLU1OovK4PVRZ2imLY261d46fQn/iFOjyM6Mb3HrecxzNo/CY0Jat2N1LIJ9dx5TFfBBmi0eiCiq+foz/5gGcv67Bj2sWb6zhmvD97zTAFx/mf94d2eLPpk8FOFdV7UBQ==</Data>
    </Cred>
</Creds>
<NewCred>
    <Cred type="PIN" subType="MPIN">
        <Data>2.0|rWTunhgMF8IojvDkoEM4UnG6B9z9WqC9sxDwKh+Km4m8A1z9ZqfeGLt9NY8Tq/CZ073fpvbx5eXZMp+B3rhzIqm/QhjDcpNeDsuW745KIo//eM5aY+bDsqJUrl4TM0tS3vt9DV+kLuvcrCkQCgeVeKRAB5QpHEtKybyI9gOPlb3U5OhwZ8Uxqe4VkRAzWBtKchmyL8f5Vky3BAsXejIcV70LRwdLhq0XNqSYj8ROEOacHekBfAw6ohP0+KOJpituloB/y82KHExYE56WO67tblYcci2/g3ZkyZNSCREGGE8HyHZNexvYKcjkHcbnRILZbQfq+dlp1/0QxjWFT6ngEQ==</Data>
    </Cred>
</NewCred>
</Payer>
</upi:ReqSetCre>
EOT;
            break;

            case 'ReqRegMob':
            $str = <<<EOT
<upi:ReqRegMob xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="ReqRegMob"/>
<Payer addr="hari@razor" name="Hari Ram" seqNum="1" type="PERSON" code="0000">
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
<Detail name="IFSC" value="RAZR"/>
<Detail name="ACTYPE" value="SAVINGS"/>
<Detail name="ACNUM" value="8861670264"/>
</Ac>
<Ac addrType="MOBILE">
<Detail name="MOBNUM" value="918861670264"/>
</Ac>
</Payer>
<RegDetails type="FORMAT1">
<Detail name="MOBILE" value="918861670264"/>
<Detail name="CARDDIGITS" value="123456"/>
<Detail name="EXPDATE" value="1224"/>
<Creds>
<Cred type="OTP" subType="SMS">
<Data code="NPCI" ki="20150822">2.0|wjUDl1U9zVyzXuE8uW3B3H8ldTatFxTXRsS6fmCtOkHUHaVsPBv9PxWgM0cs9c5Z2Vnbr6ByUzRuF0s3/vXre9WHripeygg7/FS8aXfWRf68LEYuy1biNuR4d8TTLjJb7SUqg8nDp856sZpWgex51FqG6VHQ3OSyb0AQHQ/REJcm0KMWXAcaCTllf32yKrgYVnU0rVS9HerTq5nv9ar7ERLU1OovK4PVRZ2imLY261d46fQn/iFOjyM6Mb3HrecxzNo/CY0Jat2N1LIJ9dx5TFfBBmi0eiCiq+foz/5gGcv67Bj2sWb6zhmvD97zTAFx/mf94d2eLPpk8FOFdV7UBQ==</Data>
</Cred>
<Cred type="PIN" subType="MPIN">
<Data code="NPCI" ki="20150822">2.0|rWTunhgMF8IojvDkoEM4UnG6B9z9WqC9sxDwKh+Km4m8A1z9ZqfeGLt9NY8Tq/CZ073fpvbx5eXZMp+B3rhzIqm/QhjDcpNeDsuW745KIo//eM5aY+bDsqJUrl4TM0tS3vt9DV+kLuvcrCkQCgeVeKRAB5QpHEtKybyI9gOPlb3U5OhwZ8Uxqe4VkRAzWBtKchmyL8f5Vky3BAsXejIcV70LRwdLhq0XNqSYj8ROEOacHekBfAw6ohP0+KOJpituloB/y82KHExYE56WO67tblYcci2/g3ZkyZNSCREGGE8HyHZNexvYKcjkHcbnRILZbQfq+dlp1/0QxjWFT6ngEQ==</Data>
</Cred>
</Creds>
</RegDetails>
</upi:ReqRegMob>
EOT;
                break;

            case 'GetToken':
                $method = 'ReqListKeys';
                $data = $params['device_id'] . "|" . $params['app_id'] . "|" . $params['mobile'] . "|" . $params['challenge'];

                $str = <<<EOT
<upi:ReqListKeys xmlns:upi="http://npci.org/upi/schema/">
<Head ver="1.0" ts="$ts" orgId="$orgId" msgId="{$msgId}"/>
<Txn id="$txnId" note="NOTE" refId="{$ids[0]}" refUrl="$refUrl" ts="$ts" type="GetToken"/>
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

        return [$txnId, $msgId];
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
                "Accept"        => 'application/xml',
            ],
            'content'   =>  $signedXml,
            'options'   =>  [
                /**
                 * TODO: Verify the cert properly. The issue
                 * here is that setting verify to the cert
                 * doesn't work because their hostname
                 * on the cert is npci.org.in, and they
                 * want us to make requests directly
                 * to the IP address.
                 *
                 * storage_path('certs/npci.pem')
                 */

                'verify'    =>  false
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
                'response'  => $response->body,
                'status'    => $response->status_code,
                'headers'   => $response->headers
            ]);

        return true;
    }
}
