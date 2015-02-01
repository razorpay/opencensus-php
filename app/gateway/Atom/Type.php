<?php

namespace Gateway\Atom;

class Transaction
{
    const CARD = 'CCFundTransfer';
    const NETBANKING = 'NBFundTransfer';

    public static function getType($method)
    {
        if ($method === 'card')
        {
            return self::CARD;
        }
        else if ($method === 'netbanking')
        {
            return self::NETBANKING;
        }
    }
}