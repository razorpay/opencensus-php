<?php

namespace RZP\Encryption;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $pgpEncryptionRules =[
        PGPEncryption::SECRET      => 'required|string',
        PGPEncryption::PUBLIC_KEY  => 'required|string',
        PGPEncryption::PRIVATE_KEY => 'required|string',
    ];
}
