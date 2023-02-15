<?php

namespace RZP\Models\Settlement\Processor\OPGSPImportICICI;

class Constants
{
    const FILE_BATCH_SIZE = 12000;
    const INVOICE_BATCH_SIZE = 1000;

    const REQUEST_ACTION_PAYMENT = 'capture';
    const REQUEST_ACTION_REFUND = 'refund';
    const REQUEST_ACTION_CHARGEBACK = 'chargeback';
    const REQUEST_ACTION_CHARGEBACK_REVERSAL = 'chargeback_reversal';
    const CONSOLIDATED_SHEET_FILE_NAME = 'Consolidated';
    const TRANSACTIONAL_SHEET_FILE_NAME = 'Transactional';


}
