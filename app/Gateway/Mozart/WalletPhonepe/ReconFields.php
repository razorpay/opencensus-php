<?php

namespace RZP\Gateway\Mozart\WalletPhonepe;

class ReconFields
{
    const FEE                   = 'fee';

    const IGST                  = 'igst';

    const CGST                  = 'cgst';

    const SGST                  = 'sgst';

    const FROM                  = 'from';

    const RZP_ID                = 'merchantreferenceid';

    const AMOUNT                = 'amount';

    const ORDER_ID              = 'MerchantOrderId';

    const PHONEPE_ID            = 'phonepereferenceid';

    const PAYMENT_TYPE          = 'paymenttype';

    const CREATION_DATE         = 'creationdate';

    const SETTLEMENT_DATE       = 'settlementdate';

    const TRANSACTION_DATE      = 'transactiondate';

    const BANK_REFERENCE_NO     = 'bankreferenceno';

    const TAXES                 = [self::IGST, self::CGST, self::SGST];

    const PAYMENT               = 'PAYMENT';

    const REFUND                = 'REFUND';
}
