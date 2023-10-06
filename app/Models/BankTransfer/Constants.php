<?php

namespace RZP\Models\BankTransfer;

class Constants
{
    //Commission fee will always be in USD
    const COMMISSION_FEE_FOR_CURRENCY_CLOUD_PAYOUT = 30;

    const MINIMUM_CURRENCY_CLOUD_PAYOUT_AMOUNT = 100;

    const PAYOUT_ENTRIES_PER_PAGE = 25;

    const PAYEE_ACCOUNT_MINIMUM_LENGTH = 12;

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

    const ALCOHOL                       = '5813';
    const GAMBLING                      = '7995';
    const OUTBOUND_TELEMARKTING         = '5966';
    const PAWN_SHOPS                    = '5933';
    const POLITCAL_ORGANIZATIONS        = '8651';
    const PRECIOUS_STONES_AND_METALS    = '5094';
    const SEEDS_OR_PLANTS               = '5193';
    const TOBACCO                       = '5993';

    // List as per: https://razorpay.atlassian.net/browse/CB-1864
    const BLACKLISTED_MCC_FOR_CURRENCY_CLOUD = [
        self::ALCOHOL,
        self::GAMBLING,
        self::OUTBOUND_TELEMARKTING,
        self::PAWN_SHOPS,
        self::POLITCAL_ORGANIZATIONS,
        self::PRECIOUS_STONES_AND_METALS,
        self::SEEDS_OR_PLANTS,
        self::TOBACCO,
    ];
}
