<?php
namespace RZP\Tests\Unit\Gateway;

use DOMDocument;
use DOMNode;
use Carbon\Carbon;
use RobRichards\XMLSecLibs\XMLSecEnc;
use RuntimeException;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use UnexpectedValueException;

use RZP\Gateway\Mpi\Blade\XmlseclibsAdapter;
use RZP\Gateway\Mpi\Blade\Gateway as BladeGateway;
use RZP\Gateway\Mpi\Blade\Mock\Gateway as BladeMockGateway;
use RZP\Tests\TestCase;

class BladeSignatureTest extends TestCase
{
    private function runVerifyOnXml(string $filename)
    {
        $xml = file_get_contents(__DIR__. '/' . $filename);

        $dom = new \DOMDocument;

        $dom->loadXML($xml);

        $adapter = new XmlseclibsAdapter;

        $adapter->setRootCertFingerprints(BladeMockGateway::ROOT_CERT_FINGERPRINTS);

        return $adapter->verify($dom);
    }

    public function testSigVerify()
    {
        $data = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#"><CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"></CanonicalizationMethod><SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"></SignatureMethod><Reference URI="#245495394"><DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"></DigestMethod><DigestValue>FxcQ+Bx+XjGCoew8DlkRHL08fZg=</DigestValue></Reference></SignedInfo>';
        $signature = "ETp2ZiJ5wRFPAzykU47En2nHucLW9jBAztYQrGVMMf/HjDuhywg4MrJ4/jqzGFWYGbJh8t153DsLX3DaH7BT/ikOv/Ksd89LC0Txz26SQY7hE07Z9XBndP2NuXGIpSxEPDd57p0u/qsnWKma1ta4I099Yq+IFrhYvArTBkeCi268kiK5eIjYxnx/Q3a5sNbye5VR0zCZrOnniu4GE0tdZESCP9y7M7IvqELDamU/pyaxXbb8GDulDLs/cqbLVM7sqVDBFj3VCtlN5MTPHb/EUYvw/ZtQSxgbQa9qHg3c497u/iu057nbx94VIj0v0c/6agWmA7SN09kKn/swAF8icw==";
        $certfile = file_get_contents(__DIR__.'/certs.crt');

        $verify = openssl_verify($data, base64_decode($signature), $certfile, 1);

        $this->assertTrue($verify === 1, "Raw verification should work");
    }

    public function testXMLSignature()
    {
        $xml = file_get_contents(__DIR__.'/PARes.xml');

        $objXMLSecDSig = new XMLSecurityDSig();

        $dom = new \DOMDocument;
        $dom->validateOnParse = true;
        $this->assertTrue($dom->loadXML($xml), "XML Validation should pass");

        $objDSig = $objXMLSecDSig->locateSignature($dom);
        $this->assertTrue($objDSig != false, 'Signature should be located');

        $objXMLSecDSig->canonicalizeSignedInfo();
        $objXMLSecDSig->idKeys = ['id'];

        $refValid = $objXMLSecDSig->validateReference();

        $this->assertTrue($refValid, 'Reference should be valid');

        $objKey = $objXMLSecDSig->locateKey();

        $objKeyInfo = XMLSecEnc::staticLocateKeyInfo($objKey, $objDSig);

        $this->assertTrue($objKeyInfo->key != null, "Key Locator should work");
        $ret = $objXMLSecDSig->verify($objKey);

        $this->assertTrue($ret === 1, "Signature should be valid");
    }

    /**
     * However, the certificate expired on 30 Mar 2017, so we set the date back
     */
    public function testXmlSecLibAdapterVerify()
    {
        $knownDate = Carbon::create(2017, 3, 25, 12, null, null);

        Carbon::setTestNow($knownDate);

        $ret = $this->runVerifyOnXml('PARes.xml');

        $this->assertTrue($ret, "XmlseclibsAdapter should verify the PARes");

        Carbon::setTestNow();
    }

    public function testXmlSecLibAdapterVerifyWithCurrentDate()
    {
        Carbon::create(2017, 9, 35, 12, null, null);

        $ret = $this->runVerifyOnXml('PARes.xml');

        $this->assertFalse($ret, "XmlseclibsAdapter should fail validation because of cert date");
    }

    /**
     * The cert chain here is out-of-order with the root cert in the middle
     */
    public function testParesWithRootCAInBetween()
    {
        $this->markTestSkipped();

        $ret = $this->runVerifyOnXml('PAResWCertIssue.xml');

        $this->assertTrue($ret, "XmlseclibsAdapter should verify the PARes");
    }

    public function testParesWithKeyInfoNs()
    {
        $this->validateSignatue('CorpPares.txt');
    }

    public function testParesWithoutKeyInfoNs()
    {
        $this->validateSignatue('IciciPares.txt');
    }

    public function testParesWithInvertedChain()
    {
        $this->validateSignatue('WlpAcsPares.txt', Carbon::create(2018, 6, 28, 12, null, null));
    }

    protected function validateSignatue($file, $dt = null)
    {
        $knownDate = $dt ?: Carbon::create(2017, 3, 25, 12, null, null);

        Carbon::setTestNow($knownDate);

        $pares = file_get_contents(__DIR__. '/MockData/' . $file);

        $decodePares = base64_decode($pares);

        $paresXml = gzinflate(substr($decodePares, 2));

        $blade = new BladeGateway;

        $e = null;

        try
        {
            $this->invokeMethod($blade, 'validateParesSignature', [$paresXml]);
        }
        catch (Exception $e)
        {
            ;
        }

        $this->assertEquals(null, $e);

        Carbon::setTestNow();
    }

    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
