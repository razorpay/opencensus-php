<?php

namespace RZP\Gateway\Mozart\WalletPaypal;

class ReconFields
{
    const AMOUNT                   = 'Amount';

    const ACTION_ID                = 'Custom_Id';

    const PAYPAL_MERCHANT_ID       = 'Gateway_Merchant_ID';

    const TRANSACTION_ID           = 'Gateway_Transaction_ID';

    const GATEWAY                  = 'Method';

    const PAY_ID                   = 'RZP_Transaction_ID';

    const TYPE                     = 'Type';

    const CURRENCY                 = 'currency_code';

    const TIME                     = 'Payment_Initiation_Time';

    const CHARGES                  = 'PayPal_Charges';
}
