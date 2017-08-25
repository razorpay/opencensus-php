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

    // E-Mandate Request Fields
    const STANDING_INSTRUCTIONS = 'SI';
    const EMD_PAYMENT_DATE      = 'PMT_DT';
    const PAYMENT_TYPE          = 'PMT_TY';
    const PAYMENT_FREQ          = 'PMT_FRQ';
    const NUM_INSTALLMENTS      = 'NO_INST';
    const AUTO_PAY_AMOUNT       = 'AUTO_PAY_AMOUNT';
    const SI_END_DATE           = 'SI_END_DATE';
    const REFERENCE_ID          = 'RID';
}
