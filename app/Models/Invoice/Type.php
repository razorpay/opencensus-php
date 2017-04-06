<?php

namespace RZP\Models\Invoice;

class Type
{
    const ECOD    = 'ecod';
    const INVOICE = 'invoice';
    const LINK    = 'link';

    public static function isTypeValid($type)
    {
        return (defined(__CLASS__ . '::' . strtoupper($type)));
    }

    public static function checkType($type)
    {
        if (self::isTypeValid($type) === false)
        {
            throw new \InvalidArgumentException('Not a valid type: ' . $type);
        }
    }

    /**
     * Get invoice type's label, which will be used in public error descriptions.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getLabel($type)
    {
        self::checkType($type);

        switch ($type)
        {
            case self::LINK:
            case self::ECOD:
                return 'Payment Link';

            case self::INVOICE:
                return 'Invoice';
        }
    }
}
