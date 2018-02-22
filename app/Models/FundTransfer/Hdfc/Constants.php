<?php

namespace RZP\Models\FundTransfer\Hdfc;

use RZP\Models\FundTransfer\Mode;

class Constants
{
    /**
     * Domain name to be used for the file name.
     */
    const DOMAIN                = 'RAZORPAY';

    /**
     * 4 digit client code which will be used while sending the settlement file
     */
    const CLIENT_CODE           = 'RS72RB';

    const NAME                  = 'HDFC';

    const IFSC_IDENTIFIER       = 'HDFC';

    // Transaction mode accepted by HDFC
    const RTGS                  = 'R';
    const NEFT                  = 'N';
    const IFT                   = 'I';

    // Unused transaction modes
    const DRAFT                 = 'D';
    const CHEQUE                = 'C';
    const PAY_ORDER_PRINTING    = 'H';

    const MODE_MAPPING = [
        Mode::NEFT    => self::NEFT,
        Mode::RTGS    => self::RTGS,
        Mode::IFT     => self::IFT
    ];
}
