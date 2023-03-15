<?php

namespace RZP\Models\EMandate;

class Constants
{
    const EMANDATE_MERCHANT_CONFIGURATIONS = "emandate_merchant_configurations";
    
    const RETRY_ATTEMPTS = "retry_attempts";
    
    const COOLDOWN_PERIOD = "cooldown_period";
    
    const TEMPORARY_ERRORS_ENABLE_FLAG = "temporary_errors_enable_flag";
    
    const PERMANENT_ERRORS_ENABLE_FLAG = "permanent_errors_enable_flag";
    
    const EMANDATE_CONFIG_FIELDS = [
        self::RETRY_ATTEMPTS,
        self::COOLDOWN_PERIOD,
        self::TEMPORARY_ERRORS_ENABLE_FLAG,
        self::PERMANENT_ERRORS_ENABLE_FLAG
    ];
    
    const MERCHANT_IDS = "merchant_ids";
}