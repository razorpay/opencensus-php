<?php

namespace RZP\Models\Partner\Commission\Invoice;

class Constants
{
    const INVOICE_ID  = 'invoice_id';
    const INVOICE_IDS = 'invoice_ids';
    const CREATE_TDS  = 'create_tds';

    const SKIP_PROCESSED        = 'skip_processed';
    const UPDATE_INVOICE_STATUS = 'update_invoice_status';

    // Minimum no.of subMs needed to be added for a partner to view invoice
    const VIEW_INVOICE_MIN_SUBM_COUNT = 3;
}
