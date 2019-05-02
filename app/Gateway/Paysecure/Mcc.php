<?php
namespace RZP\Gateway\Paysecure;

use Cache;

use RZP\Models\Admin\ConfigKey;

class Mcc
{
    public static function getMappedMcc($mcc)
    {
        $blacklistedMccs = Cache::get(ConfigKey::PAYSECURE_BLACKLISTED_MCCS);

        if (empty($blacklistedMccs[$mcc]) === false)
        {
            return $blacklistedMccs[$mcc];
        }

        return $mcc;
    }
}
