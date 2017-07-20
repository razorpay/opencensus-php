<?php

namespace RZP\Models\Emi\Banks\Icici;

class Headers
{
    const EMI_ID              = 'EMI_ID';
    const TRANSACTION_TIME    = 'Transaction Date/Time';
    const CARD_NUMBER         = 'Card No.';
    const AMOUNT              = 'Amount';
    const AUTH_CODE           = 'Auth Code/ Approval Code';
    const SCHEME_CODE         = 'Scheme Code';
    const TENURE              = 'Tenure';
    const MERCHANT_SUBVENTION = 'Merchant Subvention';
    const CUSTOMER_SUBVENTION = 'Customer Subvention';
    const DISCOUNT_AMOUNT     = 'Discount/ Cashback Amount';
    const DISCOUNT_PERCENTAGE = 'Discount/Cashback(%)';
    const CASHBACK            = 'Cashback (Y/N)';
    const MANUFACTURER        = 'Manufacturer';
    const MERCHANT_NAME       = 'Merchant Name';
    const PINELAB_NAME        = 'Pinelabs Merchant Name';
    const ISSUER              = 'Issuer';
    const ACQUIRER             = 'Acquirer';
    const SETTLEMENT_TIME     = 'Settlement Time';
    const SUBVENTION_PAYABLE  = 'Subvention Payable to Issuer';
    const SUBVENTION_AMOUNT   = 'Subvention Amount (Rs.)';
    const ADDITIONAL_CASHBACK = 'Addition Cashback';
}
