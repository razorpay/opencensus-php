<?php

namespace RZP\Models\FundTransfer;

class Mode
{
    const RTGS = 'RTGS';
    const IMPS = 'IMPS';
    const NEFT = 'NEFT';
    const IFT  = 'IFT';
    const DD   = 'DD';
    const FT   = 'FT';

    /**
     * gives the list of modes which are allowed for 24x7 transfers
     *
     * @return array
     */
    public static function get24x7TransferModes(): array {
        return [
            self::IMPS,
        ];
    }
}
