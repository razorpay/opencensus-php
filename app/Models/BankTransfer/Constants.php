<?php

namespace RZP\Models\BankTransfer;

use RZP\Models\Bank\IFSC;

class Constants
{
    const CONTACT = '9999009999';
    const EMAIL   = 'ecollect@razorpay.com';

    const RAZORP  = 'RAZORP';
    const RZP     = 'RZP';

    const MASTER = [
        IFSC::YESB => self::RAZORP,
        IFSC::KKBK => self::RZP,
    ];

    const IFSC = [
        IFSC::YESB => '<yesbank branch ifsc code>',
        IFSC::KKBK => '<kotak branch ifsc code>',
    ];
}
