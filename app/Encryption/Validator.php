<?php

namespace RZP\Encryption;

use phpseclib\Crypt\AES;
use RZP\Base;

class Validator extends Base\Validator
{
    protected static $pgpEncryptionRules =[
        PGPEncryption::PUBLIC_KEY  => 'sometimes|string',
        PGPEncryption::PRIVATE_KEY => 'sometimes|string',
        PGPEncryption::PASSPHRASE  => 'sometimes|string',
        PGPEncryption::USE_ARMOR   => 'sometimes|int|in:0,1',
    ];

    protected static $aesEncryptionRules = [
        AESEncryption::MODE        => 'required|integer',
        AESEncryption::IV          => 'sometimes|string',
        AESEncryption::SECRET      => 'required|string',
    ];

    protected static $aesGcmEncryptionRules = [
        AESEncryption::IV          => 'sometimes|string',
        AESEncryption::SECRET      => 'required|string',
    ];
}
