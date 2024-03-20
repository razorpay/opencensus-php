<?php

namespace RZP\Models\Upi\Turbo;

use RZP\Error\Error;

class Constants
{
    // Error Mapping Constants
    const COMMON            = 'common';
    const FALLBACK          = 'fallback';
    const GATEWAY           = 'gateway';
    const GATEWAYS          = 'gateways';
    const ERROR_CODE        = 'error_code';
    const PUBLIC_ERROR_CODE = 'public_error_code';
    const PAYER_ACCOUNT_TYPE = 'payer_account_type';

    const UPI_ERROR_CODES_DIR_PATH                   = "error_codes/error_codes/pg/upi/";
    const PG_COMMON_ERROR_CODES_FILE_PATH            = "error_codes/error_codes/pg/common/internal_error_codes.json";
    const PG_UPI_ERROR_CODES_FILE_PATH               = "error_codes/error_codes/pg/upi/internal_error_codes.json";
    const PG_UPI_GATEWAY_ERROR_MAPPING_DIR_PATH      = 'error_codes/error_codes/pg/upi/mapper/';
    const UPI_COMMON_GATEWAY_ERROR_MAPPING_FILE_PATH = 'error_codes/error_codes/pg/upi/gateway/common/gateway_error_code.json';

    const TURBO_ERROR_CODE_FIELDS = [
        self::PUBLIC_ERROR_CODE,
        Error::ERROR_DESCRIPTION,
        Error::REASON,
        Error::SOURCE,
        Error::STEP
    ];

    const FALLBACK_PUBLIC_ERROR_CODE   = "SERVER_ERROR";
    const FALLBACK_REASON              = "server_error";
    const FALLBACK_ERROR_DESCRIPTION   = "We are facing some trouble completing your request at the moment. Please try again shortly.";
    const FALLBACK_SOURCE              = "internal";
    const FALLBACK_STEP                = "payment_authorization";

    // Prefetch Constants
    const TYPE                      = "type";
    const MESSAGE                   = "message";
    const CUSTOMER_IDENTIFIER_TYPE  = "customer_identifier_type";
    const CUSTOMER_IDENTIFIER_VALUE = "customer_identifier_value";
    const ACKNOWLEDGE               = "acknowledge";
    const TIMESTAMP                 = "timestamp";
    const METADATA                  = "metadata";
    const PREFETCH_BANK             = "prefetch_bank";
    const PRIORITY                  = "priority";
    const DISPLAY_NAME              = "display_name";
    const IIN                       = "iin";
    const BANK_LOGO                 = "bank_logo";
}
