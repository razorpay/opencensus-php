<?php

namespace RZP\Models\Merchant\Email;

class Type
{
    const PARTNER_DUMMY = 'partner_dummy';
    const SUPPORT       = 'support';
    const REFUND        = 'refund';
    const DISPUTE       = 'dispute';
    const CHARGEBACK    = 'chargeback';
    const RZPINTERNAL   = 'rzpinternal';

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
        $class      = new \ReflectionClass(__CLASS__);

        $validTypes = $class->getConstants();

        return in_array($type, $validTypes, true);

    }
}
