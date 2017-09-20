<?php

namespace RZP\Encryption;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $pgpEncryptionRules =[
        PGPEncryption::SECRET      => 'required|string',
        PGPEncryption::PUBLIC_KEY  => 'sometimes|string',
        PGPEncryption::PRIVATE_KEY => 'sometimes|string',
    ];
}
