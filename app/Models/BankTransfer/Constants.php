<?php

namespace RZP\Models\BankTransfer;

class Constants
{
    //Commission fee will always be in USD
    const COMMISSION_FEE_FOR_CURRENCY_CLOUD_PAYOUT = 30;

    const MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT = 100;

    const PAYOUT_ENTRIES_PER_PAGE = 25;

    const COMMISSION_TRANSFER_REASON = "Commission Fee for the payout on mid";

    const HOUSE_ACCOUNT_TRANSFER_REASON = "Sub Account Transfer to House";

    const CURRENCY_CLOUD_PAYOUT_MAPPING_WITH_OUR_STATUS = [
        "new"                   => 'in_progress',
        "ready_to_send"         => 'in_progress',
        "completed"             => 'success',
        "failed"                => 'failed',
        "released"              => 'in_progress',
        "suspended"             => 'failed',
        "awaiting_authorisation"=> 'in_progress',
        "submitted"             => 'in_progress',
        "authorised"            => 'in_progress',
        "deleted"               => 'failed'
    ];
}
