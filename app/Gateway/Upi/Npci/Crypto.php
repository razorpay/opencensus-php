<?php

namespace RZP\Gateway\Upi\Npci;

use DOMDocument;
use phpseclib\Crypt\RSA;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecEnc;

/**
 * Handles all the Cypto code for the
 * gateway. Primarily two functions:
 *
 * - decrypt: Decrypts cred blocks
 * - sign: Signs an XML document
 */
class Crypto
{
    const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';

    const TRANSFORMS = [
        self::ENVELOPED
    ];

    public function __construct(array $config, string $mode = 'test')
    {
        $this->config = $config;
    }

    /**
     * Decrypts encrypted text from UPI (MPIN etc)
     * @param  string $data
     * @return string
     */
    public function decrypt($data)
    {
        $data = base64_decode($data);

        $rsa = $this->getRSAInstance();

        $key = $this->getDecryptionKey();

        $rsa->loadKey($key);

        return $rsa->decrypt($data);
    }

    protected function makeDOMDocument(string $xml)
    {
        $xmlDoc = new DOMDocument('1.0', 'UTF-8');

        $xmlDoc->loadXML($xml);

        return $xmlDoc;
    }

    protected function verifySignature(string $xml)
    {
        $stamp = new Stamp(null);

        $xmlDoc = $this->makeDOMDocument($xml);

        assertTrue($stamp->locateSignature($xmlDoc));

        $stamp->canonicalizeSignedInfo();

        assertTrue($stamp->validateReference());

        $objKey = $stamp->locateKey();

        $objKey->loadKey($this->getSigningPublicKey());

        $verify = $stamp->verify($objKey);

        // Calls openssl_verify, which returns 1 on success, 0 on failure, -1 on error
        return ($verify === 1);
    }

    public function sign(string $xml)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . str_replace("\n", "", $xml);

        $xmlDoc = $this->makeDOMDocument($xml);

        $stamp = new Stamp(null);

        /**
         * This is not the method that UPI specifies.
         * They ask for C14N, but there are weird namespace issues that crop up
         * if we try to use that
         */
        $stamp->setCanonicalMethod(Stamp::EXC_C14N);

        $stamp->canonicalizeSignedInfo();

        $stamp->addReference(
            $xmlDoc,
            Stamp::SHA256,
            self::TRANSFORMS,
            ['force_uri' => true]
        );

        $stamp->sign($this->getSigningKey());

        $stamp->addKeyInfo($this->getSigningPublicKey(), true);

        $stamp->appendSignature($xmlDoc->documentElement);

        $signed = $xmlDoc->saveXML();

        // We take care not to pass anything but the XML doc string
        // to verify the signature
        assertTrue($this->verifySignature($signed));

        return $signed;
    }

    protected function getRSAInstance()
    {
        /**
         * We need to do this to use PCCS 1.5 instead of 1.7
         * which is the default. This is because of what the
         * bank uses on the other side.
         */
        if (defined('CRYPT_RSA_PKCS15_COMPAT') === false)
        {
            define('CRYPT_RSA_PKCS15_COMPAT', true);
        }

        $rsa = new RSA();

        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);

        return $rsa;
    }

    /**
     * This is the private key used for
     * decrypting responses we get from the
     * gateway server
     * @see getPublicKey
     * @return string Private Key
     */
    protected function getDecryptionKey()
    {
        $key = $this->config['test_decryption_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }

    protected function getSigningKey()
    {
        $key = $this->config['test_signing_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        $key = trim(str_replace('\n', "\n", $key));

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, array('type'=>'private'));

        // function loadKey($key, $isFile=false, $isCert = false)
        $objKey->loadKey($key);

        return $objKey;
    }

    protected function getSigningPublicKey()
    {
        $key = $this->config['test_signing_public_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }
}
