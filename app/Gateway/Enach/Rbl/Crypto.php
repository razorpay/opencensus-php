<?php

namespace RZP\Gateway\Enach\Rbl;

use DOMDocument;
use phpseclib\Crypt\RSA;
use RobRichards\XMLSecLibs;

class Crypto
{
    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    const TRANSFORMS = [
        self::ENVELOPED
    ];

    protected $privateKey;

    protected $encryptionCertificatePath;

    protected $signingCertificatePath;

    public function __construct(array $config = null)
    {
        $this->config = $config;
    }

    public function setPrivateKey($key = null)
    {
        if(isset($key) === true)
        {
            $key = trim(str_replace('\n', "\n", $key));
        }
        else
        {
            $key = trim(str_replace('\n', "\n", $this->config['test_emandate_private_key']));
        }

        $this->privateKey = $key;
    }

    public function setEncryptionCertificatePath($path)
    {
        $this->encryptionCertificatePath = $path;
    }

    public function setSigningCertificatePath($path)
    {
        $this->signingCertificatePath = $path;
    }

    public function decrypt($data)
    {
        $data = base64_decode($data);

        $rsa = $this->getRSAInstance('decrypt');

        return $rsa->decrypt($data);
    }

    public function encrypt($data)
    {
        $rsa = $this->getRsaInstance('encrypt');

        $encrypted = $rsa->encrypt($data);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    protected function getRsaInstance($mode)
    {
        $rsa = new RSA();

        switch ($mode)
        {

            case 'decrypt':
                $key = $this->getPrivateKey();
                $rsa->loadKey($key);
                break;

            case 'encrypt':
                $key = $this->getEncryptionPublicKey();
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

        $sign->add509Cert($this->getSigningCert(),true, false, ['subjectName' => true ]);

        $sign->sign($this->getSigningPrivateKey());

        $sign->appendSignature($xmlDoc->documentElement);

        $signedxml = $xmlDoc->saveXML();

        $signedxml = str_replace("\n", '', $signedxml);

        $signedxml = str_replace("\r", '', $signedxml);

        assertTrue($this->verifySignature($signedxml, $this->getSigningPublicKey()));

        return $signedxml;
    }

    public function verifySignature($xmlString, $key)
    {
        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $xmlDoc = $this->makeDomDocument($xmlString);

        assertTrue($sign->locateSignature($xmlDoc));

        $sign->canonicalizeSignedInfo();

        assertTrue($sign->validateReference());

        $objKey = $sign->locateKey();

        $objKey->loadKey($key);

        $verify = $sign->verify($objKey);

        // Calls openssl_verify, which returns 1 on success, 0 on failure, -1 on error
        return ($verify === 1);
    }

    protected function getSigningCert()
    {
        return (file_get_contents($this->signingCertificatePath));
    }

    public function getSigningPublicKey()
    {
        $cert = $this->getSigningCert();

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    public function getEncryptionPublicKey()
    {
        $cert = (file_get_contents($this->encryptionCertificatePath));

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    protected function getSigningPrivateKey()
    {
        $key = $this->privateKey;

        $key = trim(str_replace('\n', "\n", $key));

        $objKey = new XMLSecLibs\XMLSecurityKey(XMLSecLibs\XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        $objKey->loadKey($key);

        return $objKey;
    }

    protected function getPrivateKey()
    {
        $key = $this->privateKey;

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
