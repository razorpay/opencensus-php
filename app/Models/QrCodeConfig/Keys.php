<?php

namespace RZP\Models\QrCodeConfig;

class Keys
{
    const CUT_OFF_TIME = 'cut_off_time';

    public function isValidKey($key)
    {
        return defined(__CLASS__ . '::' . strtoupper($key));
    }
}
