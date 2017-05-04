<?php

namespace RZP\Gateway\Aeps\Icici;

class Encryptor
{
    const CERT_PATH  = 'certs\public.cer';
    const CERT_EXPIRY = '20191230';

    public function encryptUsingSessionKey($fpData, $sKey)
    {
    }

    public function encryptSessionKey($skey)
    {
        $publicKey = file_get_contents(self::CERT_PATH);

        openssl_public_encrypt($skey, $encrypted, $publicKey);

        $encoded = base64_encode($encrypted);

        return $encoded;
    }

    public function generateHmac($fpData, $sKey)
    {
    }

    public function encryptInput(array & $input)
    {
        $skey = $this->generateSkey();

        $this->encryptUsingSessionKey();

        $input['aadhaar_hmac'] = $this->generateHmac($input['aadhaar_fingerprint'], $skey);

        $input['aadhaar_fingerprint'] = $this->encryptSessionKey($input['aadhaar_fingerprint'], $skey);

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
