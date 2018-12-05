<?php

namespace RZP\Reconciliator\Base;

class InfoCode
{
    const AMOUNT_MISMATCH                   = 'AMOUNT_MISMATCH';

    const PAYMENT_ABSENT                    = 'PAYMENT_ABSENT';

    const REFUND_ABSENT                     = 'REFUND_ABSENT';

    const CARD_TYPE_ABSENT                  = 'CARD_TYPE_ABSENT';

    const CURRENCY_MISMATCH                 = 'CURRENCY_MISMATCH';

    const UNKNOWN_CARD_TYPE                 = 'UNKNOWN_CARD_TYPE';

    const UNKNOWN_RECON_TYPE                = 'UNKNOWN_RECON_TYPE';

    const DATA_MISMATCH                     = 'DATA_MISMATCH';

    const UNEXPECTED_PAYMENT                = 'UNEXPECTED_PAYMENT';

    const RAZORPAY_FAILED_PAYMENT_RECON     = 'RAZORPAY_FAILED_PAYMENT_RECON';

    const FAILED_REFUND_ARN_ABSENT          = 'FAILED_REFUND_ARN_ABSENT';
}
