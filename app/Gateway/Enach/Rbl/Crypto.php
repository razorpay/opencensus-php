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

    protected $privateKey;

    protected $encryptionCertificate;

    protected $signingCertificate;

    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    public function setPrivateKeyPath($path)
    {
        $this->privateKey = $path;
    }

    public function setEncryptionCertificatePath($path)
    {
        $this->encryptionCertificate = $path;
    }

    public function setSigningCertificatePath($path)
    {
        $this->signingCertificate = $path;
    }

    public function decrypt($data)
    {
        $data = base64_decode($data);

        $rsa = $this->getRSAInstance('response');

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
                $key = $this->getRzpPrivateKey();
                $rsa->loadKey($key);
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

    public function addSignature($xmlString)
    {
        $xmlDoc = $this->makeDomDocument($xmlString);

        $sign = new XMLSecurityDSig(null);

        $sign->setCanonicalMethod(XMLSecLibs\XMLSecurityDSig::C14N);

        $sign->canonicalizeSignedInfo();

        $sign->addReference(
            $xmlDoc,
            XMLSecLibs\XMLSecurityDSig::SHA256,
            self::TRANSFORMS,
            ['force_uri' => true]
        );

        $sign->add509Cert($this->getRzpCert(),true, false, ['subjectName' => true ]);

        $sign->sign($this->getRzpSigningKey());

        $sign->appendSignature($xmlDoc->documentElement);

        $signedxml = $xmlDoc->saveXML();

        $signedxml = str_replace("\n", '', $signedxml);

        $signedxml = str_replace("\r", '', $signedxml);

        assertTrue($this->verifySignature($signedxml));

        return $signedxml;
    }

    protected function verifySignature($xmlString)
    {
        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $xmlDoc = $this->makeDomDocument($xmlString);

        assertTrue($sign->locateSignature($xmlDoc));

        $sign->canonicalizeSignedInfo();

        assertTrue($sign->validateReference());

        $objKey = $sign->locateKey();

        $objKey->loadKey($this->getRzpPublicKey());

        $verify = $sign->verify($objKey);

        // Calls openssl_verify, which returns 1 on success, 0 on failure, -1 on error
        return ($verify === 1);
    }

    protected function getRzpCert()
    {
        return (file_get_contents($this->signingCertificate));
    }

    protected function getRzpPublicKey()
    {
        $cert = $this->getRzpCert();

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    protected function getNpciPublicKey()
    {
        $cert = (file_get_contents($this->encryptionCertificate));

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    protected function getRzpSigningKey()
    {
        //$key =  (file_get_contents($this->privateKey));

        $key = $this->config['test_emandate_private_key'];

        $key = trim(str_replace('\n', "\n", $key));

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        $objKey->loadKey($key);

        return $objKey;
    }

    protected function getRzpPrivateKey()
    {
        //$key =  (file_get_contents($this->privateKey));

        $key = $this->config['test_emandate_private_key'];

        $key = trim(str_replace('\n', "\n", $key));

        return $key;
    }

    protected function makeDomDocument(string $xml)
    {
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }
}
