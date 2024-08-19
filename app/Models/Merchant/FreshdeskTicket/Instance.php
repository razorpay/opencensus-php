<?php


namespace RZP\Models\Merchant\FreshdeskTicket;


class Instance
{
    const RZP           = 'rzp';
    const RZPSOL        = 'rzpsol';
    const RZPIND        = 'rzpind';
    const RZPCAP        = 'rzpcap';

    const EZETAP        = 'Ezetap';

    public static function isValidFdInstance($fdInstanceString)
    {
        $key = __CLASS__ . '::' . strtoupper($fdInstanceString);

        return ((defined($key) === true) and (constant($key) === $fdInstanceString));
    }
}
