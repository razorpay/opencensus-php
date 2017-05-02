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
    }
}
