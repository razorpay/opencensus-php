<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Exception;

class Version
{
    // Reconciliation using settlement entity
    // Deprecated
    const V1    = 'V1';

    // Deprecated
    // Reconciliation using fund_transfer_attempt entity
    // Column changes
    //      1. Enrichment_1 - settlement_id
    //      2. Enrichment_2 - version
    const V2    = 'V2';

    // Current version
    // Reconciliation using fund_transfer_attempt entity
    // Column changes
    //      1. Not sending any values under any enrichment columns
    //      2. Payment_details_1 - settlement_id
    //      3. Payment_details_3 - version
    const V3    = 'V3';
}
