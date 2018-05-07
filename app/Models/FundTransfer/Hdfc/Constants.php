<?php

namespace RZP\Models\FundTransfer\Hdfc;

use Razorpay\IFSC\Bank;
use RZP\Models\FundTransfer\Mode;

class Constants
{
    /**
     * Domain name to be used for the file name.
     */
    const DOMAIN                = 'RAZORPAY';

    /**
     * client code which will be used while sending the settlement file.
     */
    const CLIENT_CODE           = 'RS72RB';

    const NAME                  = 'HDFC';

    const IFSC_IDENTIFIER       = Bank::HDFC;

    // Transaction mode accepted by HDFC
    const RTGS                  = 'R';
    const NEFT                  = 'N';
    const IFT                   = 'I';

    //
    // For IMPS transfers destination account should not be of HDFC
    // Payment mode should always be IFT for HDFC - HDFC transfers
    // Limit of transaction amount is unknown at the moment
    //
    const IMPS                  = 'M';

    // Unused transaction modes
    const DRAFT                 = 'D';
    const CHEQUE                = 'C';
    const PAY_ORDER_PRINTING    = 'H';

    // Beneficiary record flags
    const ADD                   = 'A';
    const MODIFY                = 'M';
    const DISABLE               = 'D';

    //
    //Indicated that beneficiary addition mode is through file upload
    //
    const BENE_FUNCTION_TYPE    = 'U';

    //
    // Allowed values are Y/N
    // Y - Will add the same record to other payment type
    // N - Will not add the record to other payment type
    // For same bank account this should be always `N`
    //
    const COPY_TO_PAYMENT_TYPE  = 'Y';
    const DO_NOT_COPY           = 'N';

    const MODE_MAPPING = [
        Mode::NEFT    => self::NEFT,
        Mode::RTGS    => self::RTGS,
        Mode::IFT     => self::IFT,
        Mode::IMPS    => self::IMPS
    ];
}
