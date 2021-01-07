<?php

namespace RZP\Models\FundAccount\Validation;

/**
 * Class ErrorCodesMapping

 * The purpose of this file is to maintain mapping of
 * of bank status codes which are not the internal errors
 * And in all these cases fav can be marked as complete
 * till now we used to rely on internal_error field coming from fts
 * Now for fav we are storing the mapping on our end.
 * For all error codes except the ones in this file,the fav status will be failed
 * use
 */
class ErrorCodesMapping
{
    const BANK_STATUS_CODE_MAP_FOR_COMPLETED_STATE = [
        "INVALID_VPA",
        "NRE_ACCOUNT",
        "INVALID_IFSC",
        "FROZEN_ACCOUNT",
        "CLOSED_ACCOUNT",
        "DORMANT_ACCOUNT",
        "IMPS_NOT_ENABLED",
        "INVALID_ACCOUNT_NUMBER",
        "BENEFICIARY_NAME_MISMATCH",
        "MERCHANT_VALIDATION_ERROR",
        "INVALID_BENEFICIARY_ACCOUNT",
        "INVALID_BENEFICIARY_DETAILS",
        "MERCHANT_INVALID_TXN_DETAILS"
    ];
}

