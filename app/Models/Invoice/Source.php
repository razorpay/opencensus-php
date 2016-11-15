<?php

namespace RZP\Models\Invoice;

class Source
{
    const SELLER_APP = 'seller_app';

    public static function isSourceValid($source)
    {
        return (defined(__CLASS__ . '::' . strtoupper($source)));
    }

    public static function checkSource($source)
    {
        if (self::isSourceValid($source) === false)
        {
            throw new \InvalidArgumentException('Not a valid source: ' . $source);
        }
    }
}
