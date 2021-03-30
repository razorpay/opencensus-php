<?php

namespace RZP\Gateway\Netbanking\Icici;

class RequestFields
{
    const MODE                  = 'MD';
    const PAYEE_ID              = 'PID';
    const SPID                  = 'SPID';
    const PAYMENT_ID            = 'PRN';
    const ITEM_CODE             = 'ITC';
    const AMOUNT                = 'AMT';
    const CURRENCY_CODE         = 'CRN';
    const RETURN_URL            = 'RU';
    const CONFIRMATION          = 'CG';
    const ACCOUNT_NO            = 'ACNO';
    const ENCRYPTED_STRING      = 'ES';
    const PAYMENT_DATE          = 'Pmt_Date';
    const SHOW_ON_SAME_PAGE     = 'ShowOnSamePage';

    // ---------- E-Mandate Request Fields ----------

    const SI                    = 'SI';

    /**
     * Payment date to be scheduled. Can be future or current.
     * If current, a hot payment is made.
     */
    const SI_PAYMENT_DATE       = 'PMT_DT';

    /**
     * Payment date used to make the Standing Instructions debit request.
     * Can be future or current.
     */
    const SI_DEBIT_PAYMENT_DATE = 'PMT_DATE';

    /**
     * This will be either one-time or recurring
     * We would always use this for recurring.
     */
    const SI_PAYMENT_TYPE       = 'PMT_TY';

    const SI_PAYMENT_FREQ       = 'PMT_FRQ';

    /**
     * Total number of installments.
     * Should be blank for as and when frequency.
     */
    const SI_NUM_INSTALLMENTS   = 'NO_INST';

    /**
     * Recurring amount. This should be the maximum amount.
     * Only an amount lesser than this can be charged.
     */
    const SI_AUTO_PAY_AMOUNT    = 'AUTO_PAY_AMOUNT';

    /**
     * This is only for as and when frequency.
     * No recurring charge can be made after this date.
     */
    const SI_END_DATE           = 'SI_END_DATE';

    const SI_REFERENCE_NUMBER   = 'RID';

    const BID                   = 'BID';
}
