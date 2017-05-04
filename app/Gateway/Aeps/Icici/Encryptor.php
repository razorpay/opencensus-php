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

    public function encryptUsingSessionKey($fpData, $skey)
    {
        $cipher = new AES;

        $cipher->setKey($skey);

        $pidBlock = $this->createPidXml($fpData);

        return $cipher->encrypt($pidBlock);
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
        return hash('sha256', $fpData, true);
    }

    public function encryptInput(array & $input)
    {
        $skey = $this->generateSkey();

        $input['aadhaar_hmac'] = $this->generateHmac($input['aadhaar_fingerprint'], $skey);

        $input['aadhaar_fingerprint'] = $this->encryptUsingSessionKey($input['aadhaar_fingerprint'], $skey);

        $input['aadhaar_session_key'] = $this->encryptSessionKey($skey);

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
