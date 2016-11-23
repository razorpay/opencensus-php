<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Idfc\Gateway as IdfcGateway;

class IdfcGatewayTest extends TestCase
{
    public function setUp()
    {
        $this->gw = new IdfcGateway();
    }

    public function testSerialization()
    {
        $attribs = [
            'a'  =>  'b',
            'c'  =>  '1243',
            'array'         =>  [
                'hello' =>  'world',
                'upi'       => '1234'
            ]
        ];

        $str = $this->gw->convertToXML($attribs);

        $this->assertEquals('<java:a>b</java:a><java:c>1243</java:c><java:array><java:hello>world</java:hello><java:upi>1234</java:upi><java:a>b</java:a><java:c>1243</java:c><java:array><java:hello>world</java:hello><java:upi>1234</java:upi></java:array>', $str);
    }

    public function testMakeRequest()
    {
        $method = 'GenerateMerchantDEK';

        $attribs = [
            'UPI'   =>  [
                'DeviceID'  =>  '12345',
                'MobileNo'  =>  '9999999999',
                'MerchantCredentials' =>    'creds',
            ]
        ];

        sd($this->gw->makeRequest($method, $attribs));
    }
}
