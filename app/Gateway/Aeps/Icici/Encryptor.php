<?php

namespace RZP\Gateway\Aeps\Icici;

use phpseclib\Crypt\AES;
use Carbon\Carbon;

class Encryptor
{
    const CERT_PATH  = 'certs/public.cer';
    const CERT_EXPIRY = '20191230';

    protected function createPidXml($fpData)
    {
        $date = Carbon::now('Asia/Kolkata')->format('Y-m-d\TH:i:s');

        $pidBlock = '<Pid ts=' . $date . ' ver="1.0"><Bios><Bio type="FMR" posh="UNKNOWN">' . $fpData . '</Bio></Bios></Pid>';

        return $pidBlock;
    }

    public function encryptUsingSessionKey($data, $skey)
    {
        $cipher = new AES;

        $cipher->setKey($skey);

        return $cipher->encrypt($data);
    }

    public function encryptSessionKey($skey)
    {
        $publicKey = file_get_contents(__DIR__ . '/' .self::CERT_PATH);

        openssl_public_encrypt($skey, $encrypted, $publicKey);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    public function generateHmac($fpData, $skey)
    {
        $hash = hash('sha256', $fpData, true);

        $encryptedHash = $this->encryptUsingSessionKey($hash, $skey);

        return $encryptedHash;
    }

    public function encryptInput(array & $input)
    {
        $skey = $this->generateSkey();

        $pidBlock = $this->createPidXml($input['aadhaar_fingerprint']);

        $input['aadhaar_hmac'] = base64_encode($this->generateHmac($pidBlock, $skey));

        $input['aadhaar_fingerprint'] = base64_encode($this->encryptUsingSessionKey($pidBlock, $skey));

        $input['aadhaar_session_key'] = base64_encode($this->encryptSessionKey($skey));

        $input['aadhaar_cert_expiry'] = self::CERT_EXPIRY;
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
