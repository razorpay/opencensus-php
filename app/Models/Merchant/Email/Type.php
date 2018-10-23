<?php

namespace RZP\Models\Merchant\Email;

class Type
{
    const PARTNER_DUMMY = 'partner_dummy';

    /**
     * Use to strictly reject any communication even
     * if it gets verified and used by mistake
     *
     * @var array
     */
    protected static $nonCommunicationTypes = [
        self::PARTNER_DUMMY,
    ];

    public static function exists(string $type): bool
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
