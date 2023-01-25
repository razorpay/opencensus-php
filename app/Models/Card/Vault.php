<?php

namespace RZP\Models\Card;

class Vault
{
    const TOKENEX                = 'tokenex';
    const RZP_VAULT              = 'rzpvault';
    const RZP_ENCRYPTION         = 'rzpencryption';
    const AXIS                   = 'axis';
    const HDFC                   = 'hdfc';
    const PROVIDERS              = 'providers';
    const UTIB                   = 'UTIB';

    const RZP_VAULT_SCHEME       = '0';
    const RZP_ENCRYPTION_SCHEME  = '1';

    public static $schemeToVaultMapping = [
        self::RZP_VAULT_SCHEME      => self::RZP_VAULT,
        self::RZP_ENCRYPTION_SCHEME => self::RZP_ENCRYPTION,
    ];

    public static function getVaultName($scheme)
    {
        if (empty(self::$schemeToVaultMapping[$scheme]) === false)
        {
            return self::$schemeToVaultMapping[$scheme];
        }

        return self::RZP_ENCRYPTION;
    }
}
