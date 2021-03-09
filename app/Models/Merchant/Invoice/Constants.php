<?php

namespace RZP\Models\Merchant\Invoice;

final class Constants
{
    const START_YEAR  = 2017;

    const START_MONTH = 07;

    const X_INVOICE_SEPARATOR = '-';

    const INVOICE_CODE_LENGTH_FOR_X = 11;

    const GST_PERCENTAGE = 0.18;

    const ACTION_DELETE = 'delete';

    const ACTION_CREATE = 'create';

    const ACTION_BACKFILL = 'backfill';

    const MERCHANT_INVOICE_SKIPPED_MIDS_KEY = 'merchant_invoice_skipped_mids';

    const ADD_TO_SKIPPED_MIDS_LIST = 'add';

    const REMOVE_FROM_SKIPPED_MIDS_LIST = 'remove';

    const SHOW_SKIPPED_MIDS_LIST = 'show';

}
