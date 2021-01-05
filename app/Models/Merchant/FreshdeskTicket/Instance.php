<?php


namespace RZP\Models\Merchant\FreshdeskTicket;


class Instance
{
    const RZP           = 'rzp';
    const RZPSOL        = 'rzpsol';

    public static function isValidFdInstance($fdInstanceString)
    {
        $key = __CLASS__ . '::' . strtoupper($fdInstanceString);

        return ((defined($key) === true) and (constant($key) === $fdInstanceString));
    }
}
