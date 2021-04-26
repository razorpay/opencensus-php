<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Icici\Constants;

class StatementSummary
{
    const OPENING_BALANCE                 = 'opening_balance';
    const CLOSING_BALANCE                 = 'closing_balance';
    const EFFECTIVE_BALANCE               = 'effective_balance';
    const LIEN_AMOUNT                     = 'lien_amount';
    const DEBIT_COUNT                     = 'debit_count';
    const CREDIT_COUNT                    = 'credit_count';
    const STATEMENT_GENERATED_DATE        = 'statement_generated_date';
    const STATEMENT_GENERATED_DATE_FORMAT = 'd/m/Y g:i A';
}
