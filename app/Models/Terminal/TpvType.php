<?php

namespace RZP\Models\Terminal;

class TpvType
{

    // These are being placed in a different class as
    // we don't want to add to the terminal type right
    // now.
    // We can move across to other if required.
    const NON_TPV_ONLY     = 0;
    const TPV_ONLY         = 1;
    const BOTH_TPV_NON_TPV = 2;

    public static function isTpvAllowed($tpv) : bool
    {
        return (in_array($tpv, [TpvType::BOTH_TPV_NON_TPV, TpvType::TPV_ONLY], true) === true);
    }

    public static function isNonTpvAllowed($tpv) : bool
    {
        return (in_array($tpv, [TpvType::BOTH_TPV_NON_TPV, TpvType::NON_TPV_ONLY], true) === true);
    }
}
