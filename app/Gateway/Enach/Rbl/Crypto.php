<?php

namespace RZP\Gateway\Enach\Rbl;

use DOMDocument;
use phpseclib\Crypt\RSA;
use RobRichards\XMLSecLibs;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class Crypto
{
    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    const TRANSFORMS = [
        self::ENVELOPED
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function decrypt($data)
    {
        $data = base64_decode($data);

        $rsa = $this->getRSAInstance();

        $key = $this->getDecryptionKey();

        $rsa->loadKey($key);

        return $rsa->decrypt($data);
    }

    public function encrypt($data)
    {
        $rsa = $this->getRsaInstance('request');

        $encrypted = $rsa->encrypt($data);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    protected function getRsaInstance($mode)
    {
        $rsa = new RSA();

        switch ($mode)
        {

            case 'response':
                break;

            case 'request':
                $key = $this->getNpciPublicKey();
                $rsa->loadKey($key);
                break;
        }

        $rsa->setEncryptionMode(RSA::ENCRYPTION_OAEP);
        $rsa->setHash('sha256');
        $rsa->setMGFHash('sha1');

        return $rsa;
    }

    protected function getDecryptionKey()
    {
        $key = $this->config['test_decryption_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }

    protected function getNpciPublicKey()
    {
        $cert = (file_get_contents(__DIR__ . '/keys/onmag_cert.cer'));

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    public function addSignature($xmlString)
    {
        $xmlDoc = $this->makeDomDocument($xmlString);

        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $sign->setCanonicalMethod(XMLSecLibs\XMLSecurityDSig::C14N);

        $sign->canonicalizeSignedInfo();

        $sign->addReference(
            $xmlDoc,
            XMLSecLibs\XMLSecurityDSig::SHA256,
            self::TRANSFORMS,
            ['force_uri' => true]
        );

        $sign->add509Cert($this->getRzpCert(),true, false, ['subjectName' => true ]);

        $sign->sign($this->getSigningKey());

        $sign->appendSignature($xmlDoc->documentElement);

        $signedxml = $xmlDoc->saveXML();

        //$xmlDoc->save('request.xml');

        assertTrue($this->verifySignature($signedxml));

        return $signedxml;
    }

    protected function getRzpCert()
    {
        return (file_get_contents(__DIR__ . '/keys/cert.pem'));
    }

    protected function getSigningKey()
    {
        $key =  (file_get_contents(__DIR__ . '/keys/key.pem'));

        $key = trim(str_replace('\n', "\n", $key));

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        $objKey->loadKey($key);

        return $objKey;
    }

    protected function makeDomDocument(string $xml)
    {
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }

    protected function verifySignature(string $xml)
    {
        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        assertTrue($sign->locateSignature($xmlDoc));

        $sign->canonicalizeSignedInfo();

        assertTrue($sign->validateReference());

        $objKey = $sign->locateKey();

        $objKey->loadKey($this->getSigningPublicKey());

        $verify = $sign->verify($objKey);

        // Calls openssl_verify, which returns 1 on success, 0 on failure, -1 on error
        return ($verify === 1);
    }

    protected function getSigningPublicKey()
    {
        $cert = $this->getRzpCert();

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }
}
