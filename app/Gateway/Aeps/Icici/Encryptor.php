<?php

namespace RZP\Gateway\Aeps\Icici;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use phpseclib\Crypt\AES;
use RZP\Constants\Mode;

class Encryptor
{
    const CERT_PATH   = 'certs/public.cer';
    const CERT_EXPIRY = '20191230';

    const CERT_PATH_UAT   = 'certs/public_uat.cer';
    const CERT_EXPIRY_UAT = '20171105';

    protected $privateKey = '';

    protected $publicKey = '';

    protected $publicCertificatePath = '';

    protected $privateKeyPath = '';

    public function __construct($mode = AES::MODE_ECB, $iv = '')
    {
        $this->encryptionMode = $mode;

        if ($mode === AES::MODE_CBC)
        {
            $this->iv = '';
        }
    }

    public function setPrivateKey(string $key)
    {
        $this->privateKey = $key;
    }

    protected function createPidXml($fpData)
    {
        $date = Carbon::now(Timezone::IST)->format('Y-m-d\TH:i:s');

        $pidBlock = '<Pid ts="' . $date . '" ver="1.0"><Bios><Bio type="FMR" posh="UNKNOWN">' . $fpData . '</Bio></Bios></Pid>';

        return $pidBlock;
    }

    public function encryptUsingSessionKey($data, $skey)
    {
        // This is because they were stripping the first 16 characters from the data we sent
        // and hence they could not parse that json string.
        $data = str_repeat(" ", 16) . $data;

        $cipher = new AES($this->encryptionMode);

        if ($this->encryptionMode === AES::MODE_CBC)
        {
            $cipher->setIV($this->iv);
        }

        $cipher->setKey($skey);

        return base64_encode($cipher->encrypt($data));
    }

    public function decryptUsingSessionKey($data, $skey)
    {
        $cipher = new AES($this->encryptionMode);

        if ($this->encryptionMode === AES::MODE_CBC)
        {
            $cipher->setIV($this->iv);
        }

        $cipher->setKey($skey);

        $data = $cipher->decrypt(base64_decode($data));

        // First 16 characters are garbage. Need to check this with them.
        return substr($data, 16);
    }

    public function encryptSessionKey($skey, $mode, $type = 'auth')
    {
        if ($mode === Mode::LIVE)
        {
            $certPath = self::CERT_PATH;
        }
        else
        {
            $certPath = self::CERT_PATH_UAT;
        }

        $publicKey = file_get_contents(__DIR__ . '/' . $certPath);

        // For refunds
        if ($type !== 'auth')
        {
            $publicKey = $this->publicKey;
        }

        openssl_public_encrypt($skey, $encrypted, $publicKey);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    public function decryptSessionKey($skey)
    {
        $decoded = base64_decode($skey);

        openssl_private_decrypt($decoded, $decrypted, $this->privateKey);

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

    public function generateSkey($length = 16)
    {
        //TODO : make it better
        $result = '';

        foreach (range(0, $length - 1) as $index)
        {
            $result .= mt_rand(0, 9);
        }

        return $result;
    }

    public function setPublicKey(string $key)
    {
        $this->publicKey = $key;
    }

    protected function getPublicCertificatePath()
    {
        return $this->publicCertificatePath;
    }

    public function setPrivateKeyPath(string $path)
    {
        $this->privateKeyPath = $path;
    }

    protected function getPrivateKeyPath()
    {
        return $this->privateKeyPath;
    }
}
