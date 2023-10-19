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

    const PG_UPI_ERROR_CODES_FILE_PATH               = "error_codes/error_codes/pg/upi/internal_error_codes.json";
    const PG_COMMON_ERROR_CODES_FILE_PATH            = "error_codes/error_codes/pg/common/internal_error_codes.json";
    const PG_UPI_GATEWAY_ERROR_MAPPING_DIR_PATH      = 'error_codes/error_codes/pg/upi/mapper/';
    const UPI_COMMON_GATEWAY_ERROR_MAPPING_FILE_PATH = 'error_codes/error_codes/pg/upi/mapper/common.json';

    const TURBO_ERROR_CODE_FIELDS = [
        self::PUBLIC_ERROR_CODE,
        Error::INTERNAL_ERROR_CODE,
        Error::ERROR_DESCRIPTION,
    ];

    const FALLBACK_PUBLIC_ERROR_CODE   = "SYSTEM_ERROR";
    const FALLBACK_INTERNAL_ERROR_CODE = "FALLBACK_ERROR";
    const FALLBACK_ERROR_DESCRIPTION   = "Something went wrong, please try again later. Any amount deducted will be refunded within 5-7 working days.";
}
