<?php

namespace RZP\Gateway\Enach\Npci\Netbanking;

use DOMDocument;
use phpseclib\Crypt\RSA;
use RobRichards\XMLSecLibs;

use RZP\Constants\Mode;

class Crypto
{
    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    const TRANSFORMS = [
        self::ENVELOPED
    ];

    protected $privateKey;

    protected $encryptionCertificate;

    protected $signingCertificate;

    public function __construct(array $config = null, $mode = Mode::TEST)
    {
        if ($config === null)
        {
            return;
        }

        $this->config = $config;

        if ($mode === Mode::LIVE)
        {
            $key = $this->config['live_npci_emandate_private_key'];

            $this->setPrivateKey($key);
            $this->setEncryptionCertificate($this->config['live_npci_emandate_encryption_certificate']);
            $this->setSigningCertificate($this->config['live_npci_emandate_signing_certificate']);
        }
        else if ($mode === Mode::TEST)
        {
            $key  = $this->config['test_emandate_private_key'];
            $cert = file_get_contents(__DIR__ . '/keys/onmag_cert.cer');
            $sign = file_get_contents(__DIR__ . '/keys/cert.pem');

            $this->setPrivateKey($key);
            $this->setEncryptionCertificate($cert);
            $this->setSigningCertificate($sign);
        }
    }

    public function setPrivateKey($key)
    {
        $key = trim(str_replace('\n', "\n", $key));

        $this->privateKey = $key;
    }

    public function setEncryptionCertificate($cert)
    {
        $cert = trim(str_replace('\n', "\n", $cert));
        $this->encryptionCertificate = $cert;
    }

    public function setSigningCertificate($cert)
    {
        $cert = trim(str_replace('\n', "\n", $cert));
        $this->signingCertificate = $cert;
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
                break;

            case 'encrypt':
                $key = $this->getEncryptionPublicKey();
                break;
        }

        $rsa->loadKey($key);

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

        $sign->add509Cert($this->signingCertificate, true, false, ['subjectName' => true ]);

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

    public function checkSignaturePresent($xmlString)
    {
        $sign = new XMLSecLibs\XMLSecurityDSig(null);

        $xmlDoc = $this->makeDomDocument($xmlString);

        return ($sign->locateSignature($xmlDoc));
    }

    public function getSigningPublicKey()
    {
        $cert = $this->signingCertificate;

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    public function getEncryptionPublicKey()
    {
        $cert = $this->encryptionCertificate;

        $publicKeyResource = openssl_pkey_get_public($cert);

        $pubkeyInfo = openssl_pkey_get_details($publicKeyResource);

        return $pubkeyInfo['key'];
    }

    protected function getSigningPrivateKey()
    {
        $key = $this->privateKey;

        $objKey = new XMLSecLibs\XMLSecurityKey(XMLSecLibs\XMLSecurityKey::RSA_SHA256, array('type' => 'private'));

        $objKey->loadKey($key);

        return $objKey;
    }

    protected function getPrivateKey()
    {
        $key = $this->privateKey;

        return $key;
    }

    protected function makeDomDocument(string $xml)
    {
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }
}
