<?php

namespace RZP\Encryption;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $pgpEncryptionRules = [
        PGPEncryption::SECRET => 'required|string',
    ];
}
