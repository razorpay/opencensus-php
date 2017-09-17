<?php

namespace RZP\Encryption;

interface IEncryption
{
    public function encrypt(string $data) : string ;

    public function decrypt(string $data) : string ;
}