<?php

namespace RZP\Models\FundAccount\Validation;

class Status
{
    const CREATED               = 'created';
    // In case of Bank Account Validations,
    // Status = Validated will either have Beneficiary Name,
    // or error code in case of failures that are valid like
    // Transfer failed because of wrong IFSC/Name.
    const VALIDATED             = 'validated';
    const FAILED                = 'failed';
}
