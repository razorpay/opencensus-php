<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

class Constants
{
    const FILE_BATCH_SIZE = 20000;
    const PAYMENT_BATCH_SIZE = 30000;
    const INVOICE_BATCH_SIZE = 500;

    const REQUEST_ACTION_PAYMENT = 'capture';
    const REQUEST_ACTION_REFUND = 'refund';
    const REQUEST_ACTION_CHARGEBACK = 'chargeback';
    const REQUEST_ACTION_CHARGEBACK_REVERSAL = 'chargeback_reversal';
    const CONSOLIDATED_SHEET_FILE_NAME = 'Consolidated';
    const TRANSACTIONAL_SHEET_FILE_NAME = 'Transactional';

    const DISPUTE = 'dispute';

    const FIELDSTODECRYPT = ['name', 'line1', 'line2', 'city', 'state', 'country', 'zipcode'];
}
