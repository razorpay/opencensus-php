<?php

namespace RZP\Models\Partner\Commission\Invoice;

class Constants
{
    const INVOICE_ID    = 'invoice_id';
    const INVOICE_IDS   = 'invoice_ids';
    const CREATE_TDS    = 'create_tds';
    const PARTNER_IDS   = 'partner_ids';
    const INVOICE_MONTH = 'invoice_month';

    const SKIP_PROCESSED        = 'skip_processed';
    const UPDATE_INVOICE_STATUS = 'update_invoice_status';

    // Minimum no.of subM MTUs needed to be present for a partner to generate invoice
    const GENERATE_INVOICE_MIN_SUB_MTU_COUNT = 3;

    const INVOICE_TNC_UPDATED_TIMESTAMP = 1672531200;
}
