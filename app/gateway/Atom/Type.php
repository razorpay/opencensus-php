<?php

namespace Gateway\Atom;

class Transaction
{
    const CARD = 'CCFundTransfer';
    const NET_BANKING = 'NBFundTransfer';

    public static function getType($method)
    {
        if ($method === 'card')
        {
            return self::CARD;
        }
        else if ($method === 'netbanking')
        {
            return self::NET_BANKING;
        }
    }
}