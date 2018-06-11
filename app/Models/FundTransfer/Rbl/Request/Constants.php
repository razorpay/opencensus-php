<?php

namespace RZP\Models\FundTransfer\Rbl\Request;

final class Constants
{
    //
    // Here is list of Identifiers available based on the API request.
    // These keys are parent keys in the respective API responses.
    // Response will be wrapped inside these keys
    //

    // Status Response identifier
    const STATUS_RESPONSE_IDENTIFIER    = 'get_Single_Payment_Status_Corp_Res';

    // Payment Response identifier
    const TRANSFER_RESPONSE_IDENTIFIER  = 'Single_Payment_Corp_Resp';
}
