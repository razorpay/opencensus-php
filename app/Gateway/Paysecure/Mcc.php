<?php
namespace RZP\Gateway\Paysecure;

use Cache;

use RZP\Models\Admin\ConfigKey;

class Mcc
{
    public static function getMappedMcc($mcc)
    {
        $blacklistedMccs = Cache::get(ConfigKey::PAYSECURE_BLACKLISTED_MCCS);

        $defaultMcc = '7994';

        if ((empty($blacklistedMccs) === false) and
            (in_array($mcc, $blacklistedMccs) === true))
        {
            return $defaultMcc;
        }

        return $mcc;
    }
}
