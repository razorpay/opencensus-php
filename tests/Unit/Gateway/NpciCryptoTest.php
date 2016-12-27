<?php

namespace RZP\Tests\Unit\Gateway;

use RZP\Tests\TestCase;
use RZP\Gateway\Upi\Npci\Crypto;

class NpciCryptoTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $config = config('gateway.upi_npci');

        $this->c = new Crypto($config, 'test');
    }

    public function testSignature()
    {
        $str = '<upi:ReqHbt xmlns:upi="http://npci.org/upi/schema/"><Head ver="1.0" ts="2016-11-16T21:26:27+05:30" orgId="RAZOR" msgId="RAZC703F59B87D04619853C2003342564E6"/><Txn id="RAZBE13D697336B4930B6AF5E8F6F5964D9" note="HELLO WORLD" refId="RAZ928ACE686F214D3FBE634ED793E428F1" refUrl="http://www.npci.org.in/" ts="2016-11-16T21:26:27+05:30" type="Hbt" /><HbtMsg type="ALIVE" value="NA"/></upi:ReqHbt>';
        $signedXml = $this->c->sign($str);

        $this->assertTrue(strpos($signedXml, '<SignatureValue>') !== false);
        $this->assertTrue(strpos($signedXml, '<SignatureValue>') !== false);
        $this->assertTrue(strpos($signedXml, '<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>') !== false);
        $this->assertTrue(strpos($signedXml, '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>') !== false);
        $this->assertTrue(strpos($signedXml, '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>') !== false);
        $this->assertTrue(strpos($signedXml, '<KeyValue>') !== false);
    }
}
