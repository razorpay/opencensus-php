<?php

namespace RZP\Gateway\Enach\Citi;

class NachRegisterFileHeadings
{
    // Request file headings
    const SERIAL_NUMBER                 = 'Sr.no';
    const CATEGORY_CODE                 = 'Category Code';
    const CATEGORY_DESCRIPTION          = 'Category Description';
    const START_DATE                    = 'Start date';
    const END_DATE                      = 'End date';
    const CLIENT_CODE                   = 'Client code';
    const MERCHANT_UNIQUE_REFERENCE_NO  = 'Unique reference no';
    const CUSTOMER_ACCOUNT_NUMBER       = 'Account No';
    const CUSTOMER_NAME                 = 'Account Holder name';
    const ACCOUNT_TYPE                  = 'Account type';
    const BANK_NAME                     = 'Bank Name';
    const BANK_IFSC                     = 'Bank MICR / IFSC';
    const AMOUNT                        = 'Amount';

    // Additional headings in Response file
    const STATUS                        = 'Status';
    const REMARKS                       = 'Remark';
    const UMRN                          = 'UMRN';
    const LOT                           = 'Lot';
    const SOFT_COPY_RECEIVED_DATE       = 'Softcopy Received Date';
}
