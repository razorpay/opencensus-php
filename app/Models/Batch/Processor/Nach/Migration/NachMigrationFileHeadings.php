<?php

namespace RZP\Models\Batch\Processor\Nach\Migration;

class NachMigrationFileHeadings
{
    // Request file headings
    const START_DATE          = 'start_date';
    const END_DATE            = 'end_date';
    const BANK                = 'bank';
    const ACCOUNT_NUMBER      = 'account_number';
    const ACCOUNT_HOLDER_NAME = 'account_holder_name';
    const ACCOUNT_TYPE        = 'account_type';
    const IFSC                = 'ifsc';
    const MAX_AMOUNT          = 'max_amount (in Rs)';
    const UMRN                = 'umrn';
    const DEBIT_TYPE          = 'debit_type';
    const FREQ                = 'frequency';
    const METHOD              = 'method';
    const CUSTOMER_EMAIL      = 'customer_email';
    const CUSTOMER_PHONE      = 'customer_phone';

    // Additional headings in response file
    const TOKEN_CREATION_STATUS = 'TOKEN_CREATION_STATUS';
    const TOKEN_ID              = 'TOKEN_ID';
    const FAILURE_REASON        = 'FAILURE_REASON';
    const CUSTOMER_ID           = 'CUSTOMER_ID';
}
