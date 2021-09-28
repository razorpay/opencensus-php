<?php

namespace RZP\Gateway\Upi\Base;

class IntentParams
{
    const PAYEE_ADDRESS = 'pa';
    const PAYEE_NAME    = 'pn';
    const TXN_REF_ID    = 'tr';
    const TXN_ID        = 'ti';
    const TXN_NOTE      = 'tn';
    const TXN_AMOUNT    = 'am';
    const TXN_CURRENCY  = 'cu';
    const MCC           = 'mc';
    const URL           = 'url';

    // Extended Params 2.0

    const VERSION       = 'ver';
    const MODE          = 'mode';
    const QR_MEDIUM     = 'qrMedium';
    const PURPOSE       = 'purpose';
    const ORG_ID        = 'orgid';
    const SIGN          = 'sign';

    // GST Params

    const GSTIN         = 'gstIn';
    const GST_BREAKUP   = 'gstBrkUp';
    const INVOICE_NO    = 'invoiceNo';
    const INVOICE_DATE  = 'invoiceDate';
    const INVOICE_NAME  = 'invoiceName';
    const GST           = 'GST';
    const SGST          = 'SGST';
    const CGST          = 'CGST';
    const IGST          = 'IGST';
    const CESS          = 'CESS';
    const GST_INCENTIVE = 'GSTIncentive';
    const GST_PCT       = 'GSTPCT';
}
