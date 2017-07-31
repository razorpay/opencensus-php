<?php

namespace RZP\Models\Risk;

class RiskCode
{
    // Risk codes for suspected fraud payments

    const PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND = 'PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND';


    // Risk codes for confirmed fraud payments

    const PAYMENT_FAILED_DUE_TO_BLOCKED_CARD = 'PAYMENT_FAILED_DUE_TO_BLOCKED_CARD';
}
