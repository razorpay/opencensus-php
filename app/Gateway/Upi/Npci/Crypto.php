<?php

namespace RZP\Gateway\Upi\Npci;

use DOMDocument;
use RobRichards\XMLSecLibs\XMLSecurityKey;

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

    public function sign(string $xml)
    {
        file_put_contents('/tmp/signed2.xml', $this->oldSign($xml));

        $xmlDoc = (new DOMDocument);

        $xmlDoc->loadXML($xml);

        $stamp = new Stamp;

        $stamp->setCanonicalMethod(Stamp::C14N);

        $stamp->addReference(
            $xmlDoc,
            Stamp::SHA256,
            self::TRANSFORMS
        );

        $stamp->sign($this->getSigningKey());

        $stamp->addKeyInfo($this->getSigningPublicKey(), true);

        $stamp->appendSignature($xmlDoc->documentElement);

        $signed = $xmlDoc->saveXML();

        $signed =  str_replace(['ds:', ':ds'], '', $signed);

        file_put_contents('/tmp/signed.xml', $signed);

        return $signed;
    }

    protected function oldSign($xml)
    {
        $inputxml = tempnam(sys_get_temp_dir(), 'req');

        file_put_contents($inputxml, $xml);

        $outputxml = tempnam(sys_get_temp_dir(), 'res');

        $privateKey = storage_path('certs/razorpay-npci-pkcs8.key');
        $publicKey  = storage_path('certs/razorpay-npci.pub');

        chdir(app_path('../scripts'));

        shell_exec("/usr/bin/java SignatureGen '$inputxml' '$outputxml' '$privateKey' '$publicKey'");

        return file_get_contents($outputxml);
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
        $objKey->loadKey($key, false);

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
