<?php

namespace RZP\Models\Pricing\Calculator\Tax\IN;

class Constants
{
    const IGST_PERCENTAGE = 1800; // Integrated GST
    const CGST_PERCENTAGE = 900; // Central GST
    const SGST_PERCENTAGE = 900; // State GST

    // 1st July 2017 00:00:00 IST - Timestamp at which GST will begin to be levied on transactions
    const GST_START_TIMESTAMP = 1498847400;

    // '29' - Karnataka's state code
    const RZP_GST_STATE_CODE = '29';
    const RZP_STATE = 'KA';

    const CARD_TAX_CUT_OFF = 200000;
}
