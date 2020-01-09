<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl\Constants;

/**
 * Class TransactionLineItem
 * @package RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl
 * This class represents the key names for an individual line item in the RBL Account Statement
 */
class TransactionLineItem
{
    const TRANSACTION_DATE    = 'transaction_date';
    const TRANSACTION_DETAILS = 'transaction_details';
    const CHEQUE_ID           = 'cheque_id';
    const VALUE_DATE          = 'value_date';
    const BALANCE             = 'balance';
    const WITHDRAWAL_AMOUNT   = 'withdrawal_amount';
    const DEPOSIT_AMOUNT      = 'deposit_amount';
    const ITEM_DATE_FORMAT    = 'd/m/Y';
}
