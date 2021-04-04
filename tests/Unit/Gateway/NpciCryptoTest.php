<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Npci\Crypto;

class NpciCryptoTest extends TestCase
{
    const DOC = '<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/"><Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="RAZOR" msgId="RAZC703F59B87D04619853C2003342564E6"/><Txn id="RAZBE13D697336B4930B6AF5E8F6F5964D9" note="HELLO WORLD" refId="RAZ928ACE686F214D3FBE634ED793E428F1" refUrl="http://www.npci.org.in/" ts="2016-11-16T21:26:27+05:30" type="Hbt" /><HbtMsg type="ALIVE" value="NA"/></upi:ReqHbt>';

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('gateway.upi_npci');

        $this->c = new Crypto($config, 'test');
    }

    public function testSignature()
    {
        $signedXml = $this->c->sign(self::DOC);
        $php = simplexml_load_string($signedXml);

        // This is necessary for xpath expressions to work properly
        $php->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $this->assertEquals('dopnRJjF7Pao9sB/rzYGBLVle0xHGnb50dD35rDt47pp2el9unKy/NRKhehxAnpfdQPLt1sVZUFxsKMFRADbzoLMzy5nSOwJr1k9brZOFvnFyRsNVlKP4oerv+tk3brpkcWGmff7b0DryMk0y5x92hsPDg5ZjZCnjqV6Sg1h9Zo=', (string) $php->xpath('//ds:SignatureValue')[0]);

        $this->assertEquals('http://www.w3.org/2001/10/xml-exc-c14n#', (string) $php->xpath('//ds:CanonicalizationMethod[1]/@Algorithm')[0]);
        $this->assertEquals('http://www.w3.org/2000/09/xmldsig#enveloped-signature', (string) $php->xpath('//ds:Transform[1]/@Algorithm')[0]);
        $this->assertEquals('http://www.w3.org/2001/04/xmlenc#sha256', (string) $php->xpath('//ds:DigestMethod[1]/@Algorithm')[0]);
        $this->assertEquals('http://www.w3.org/2001/04/xmldsig-more#rsa-sha256', (string) $php->xpath('//ds:SignatureMethod[1]/@Algorithm')[0]);

        $this->assertEquals('ynCurP7H6524W7pOV2DDBS7YjlSr+Ti0+HxNhbL9du1NRDbLgHqeNKD0Gis6kKdEIE78Kp0qMwWXVRvFNDdcAZtsHSdwtMG97GTajYkGq0P/4UlD3IB3jvbuTcyFnDLBFd3SppeJ3osE26MrjZN72WMT2PbVROJzc81HfyFU9c0=', (string) $php->xpath('//ds:Modulus')[0]);
        $this->assertEquals('AQAB', (string) $php->xpath('//ds:Exponent')[0]);
        $this->assertEquals('xSXA34vzxKx1T24vaG0RLeagCDo6pC/Fav8RsCtXoEE=', (string) $php->xpath('//ds:DigestValue')[0]);
    }
}
