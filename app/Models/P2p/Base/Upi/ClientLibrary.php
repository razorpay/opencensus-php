<?php

namespace RZP\Models\P2p\Base\Upi;

use RZP\Models\P2p\Base\Libraries\Rules;

class ClientLibrary
{
    const CL                = 'cl';
    const CAPABILITY        = 'capability';
    const CHALLENGE         = 'challenge';
    const TOKEN             = 'token';
    const PAYLOAD           = 'payload';

    public static function rules(): Rules
    {
        return new Rules([
            ClientLibrary::CAPABILITY   => 'string',
            ClientLibrary::CHALLENGE    => 'string',
            ClientLibrary::TOKEN        => 'string',
            ClientLibrary::PAYLOAD      => 'string',
        ]);
    }
}
