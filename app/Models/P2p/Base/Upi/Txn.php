<?php

namespace RZP\Models\P2p\Base\Upi;

use RZP\Models\P2p\Base\Libraries\Rules;
use RZP\Models\P2p\Base\Libraries\ArrayBag;

class Txn extends ArrayBag
{
    const TXN   = 'txn';
    const ID    = 'id';
    const NOTE  = 'note';
    const TS    = 'ts';
    const TYPE  = 'type';

    public static function rules(): Rules
    {
        return new Rules([
            self::ID        => 'string|max:50',
            self::NOTE      => 'string|max:100',
            self::TYPE      => 'string',
        ]);
    }
}
