<?php

namespace RZP\Gateway\Aeps\Icici;

class Encryptor
{
    public function encryptUsingSessionKey()
    {
    }

    public function encryptSessionKey()
    {
    }

    public function encryptInput(array & $input)
    {
        $this->encryptUsingSessionKey();
        $this->encryptSessionKey();

        $input['aadhaar_hmac'] = 'test';
        $input['aadhaar_session_key'] = 'test';
        $input['aadhaar_cert_expiry'] = 'test';
    }
}
