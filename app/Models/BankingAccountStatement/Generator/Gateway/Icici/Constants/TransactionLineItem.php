<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants;

class TransactionLineItem
{
    const NO                      = 'no';
    const TRANSACTION_ID          = 'transaction_id';
    const VALUE_DATE              = 'value_date';
    const TRANSACTION_POSTED_DATE = 'transaction_posted_date';
    const CHEQUE_NO               = 'cheque_no';
    const DESCRIPTION             = 'description';
    const CR_DR                   = 'cr_dr';
    const TRANSACTION_AMOUNT      = 'transaction_amount';
    const AVAILABLE_BALANCE       = 'available_balance';
    const ITEM_DATE_FORMAT        = 'd/m/Y';
    const POSTED_DATE_FORMAT      = 'd/m/y g:i:s A';
}
