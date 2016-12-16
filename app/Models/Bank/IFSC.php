<?php

namespace RZP\Models\Bank;

class IFSC extends BaseBank
{
    public static function exists($code)
    {
        return defined(get_class().'::'.$code);
    }
}
