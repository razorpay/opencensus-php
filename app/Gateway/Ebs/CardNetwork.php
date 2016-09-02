<?php

namespace RZP\Gateway\Ebs;

use RZP\Exception;

class CardNetwork
{
    const VISA   = '1';
    const MC     = '2';
    const MAES   = '3';
    const DICL   = '4';
    const AMEX   = '5';
    const JCB    = '6';

    public static function map($network)
    {
        if (defined(__CLASS__ . '::' . $network))
        {
            return constant(__CLASS__ . '::' . $network);
        }

        throw new Exception\InvalidArgumentException(
            'Card Network not supported');
    }
}
