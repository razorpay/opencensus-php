<?php

namespace RZP\Gateway\Upi\Npci;

use DOMDocument;
use FR3D\XmlDSig\Adapter\XmlseclibsAdapter;
use RobRichards\XMLSecLibs;

/**
 * Handles all the Cypto code for the
 * gateway. Primarily two functions:
 *
 * - decrypt: Decrypts cred blocks
 * - sign: Signs an XML document
 */
class Crypto
{
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

    public function sign(string $xml)
    {
        $xmlDoc = (new DOMDocument);
        $xmlDoc->loadXML($xml);

        $stamp = new XmlseclibsAdapter;

        $signingKey = $this->getSigningKey();
        $stamp->setPrivateKey($signingKey);
        $stamp->setPublicKey($this->getSigningPublicKey());

        $stamp->setDigestAlgorithm(XMLSecLibs\XMLSecurityDSig::SHA256);

        $stamp->sign($xmlDoc);

        return $xmlDoc->saveXML();
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
        return trim(str_replace('\n', "\n", $key));
    }

    protected function getSigningPublicKey()
    {
        $key = $this->config['test_signing_public_key'];

        // The trim is to make sure that the key doesn't end with
        // an extra newline
        return trim(str_replace('\n', "\n", $key));
    }
}
