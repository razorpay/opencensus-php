<?php

namespace RZP\Gateway\Aeps\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use phpseclib\Crypt\AES;
use RZP\Constants\Mode;

class Encryptor
{
    const CERT_PATH       = 'certs/public.cer';
    const ICICI_CERT_PATH = 'certs/public_icici.cer';
    const CERT_EXPIRY     = '20191230';

    const CERT_PATH_UAT       = 'certs/public_uat.cer';
    const ICICI_CERT_PATH_UAT = 'certs/public_uat_icici_refund.cer';
    const CERT_EXPIRY_UAT     = '20171105';

    const MOCK_CERT_PATH_PUBLIC  = 'Mock/certificates/cert.pem';
    const MOCK_CERT_PATH_PRIVATE = 'Mock/certificates/key.pem';

    protected $mockCert = false;

    protected $privateKey = '';

    public function __construct($mode = 1, $iv = '')
    {
        $this->encryptionMode = $mode;

        if ($mode === 2)
        {
            $this->iv = '';
        }
    }

    public function setMock(bool $mock)
    {
        $this->mockCert = $mock;
    }

    public function setPrivateKey(string $key)
    {
        $this->privateKey = $key;
    }

    protected function createPidXml($fpData)
    {
        $date = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

        $pidBlock = '<Pid ts="' . $date . '" ver="1.0"><Bios><Bio type="FMR" posh="UNKNOWN">' .
                    $fpData . '</Bio></Bios></Pid>';

        return $pidBlock;
    }

    public function encryptUsingSessionKey($data, $skey)
    {
        $cipher = new AES($this->encryptionMode);

        if ($this->encryptionMode === 2)
        {
            $cipher->setIV($this->iv);
        }

        $cipher->setKey($skey);

        return base64_encode($cipher->encrypt($data));
    }

    public function decryptUsingSessionKey($data, $skey)
    {
        $cipher = new AES($this->encryptionMode);

        if ($this->encryptionMode === 2)
        {
            $cipher->setIV($this->iv);
        }

        $cipher->setKey($skey);

        return $cipher->decrypt(base64_decode($data));
    }

    public function encryptSessionKey($skey, $mode, $type = '')
    {
        if ($mode === Mode::LIVE)
        {
            $certPath = self::CERT_PATH;
        }
        else
        {
            $certPath = self::CERT_PATH_UAT;
        }

        if ($type === 'refund')
        {
            if ($mode === Mode::LIVE)
            {
                $certPath = self::ICICI_CERT_PATH;
            }
            else
            {
                $certPath = self::ICICI_CERT_PATH_UAT;
            }
        }

        if ($this->mockCert === true)
        {
            $certPath = self::MOCK_CERT_PATH_PUBLIC;
        }

        $publicKey = file_get_contents(__DIR__ . '/' . $certPath);

        openssl_public_encrypt($skey, $encrypted, $publicKey);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    /**
     * This would be used only in mocks. So, we use the mock certificate no matter which action it is.
     */
    public function decryptSessionKey($skey)
    {
        if ($this->mockCert === true)
        {
            $key = file_get_contents(__DIR__ . '/' . self::MOCK_CERT_PATH_PRIVATE);
        }
        else
        {

            $key = $this->privateKey;
        }

        $decoded = base64_decode($skey);

        openssl_private_decrypt($decoded, $decrypted, $key);

        return $decrypted;
    }

    public function generateHmac($fpData, $skey)
    {
        $hash = hash('sha256', $fpData, true);

        $encryptedHash = $this->encryptUsingSessionKey($hash, $skey);

        return $encryptedHash;
    }

    public function encryptInput(array & $input, $mode)
    {
        $skey = $this->generateSkey();

        $pidBlock = $this->createPidXml($input['aadhaar']['fingerprint']);

        $input['aadhaar']['hmac'] = $this->generateHmac($pidBlock, $skey);

        $input['aadhaar']['fingerprint'] = $this->encryptUsingSessionKey($pidBlock, $skey);

        $input['aadhaar']['session_key'] = $this->encryptSessionKey($skey, $mode);

        if ($mode === Mode::LIVE)
        {
            $input['aadhaar']['cert_expiry'] = self::CERT_EXPIRY;
        }
        else
        {
            $input['aadhaar']['cert_expiry'] = self::CERT_EXPIRY_UAT;
        }
    }

    function generateSkey($length = 16)
    {
        //TODO : make it better
        $result = '';

        foreach (range(0, $length - 1) as $index)
        {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }
}
