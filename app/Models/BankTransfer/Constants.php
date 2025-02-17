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

    const CITY_NOT_AVAILABLE            = 'not available';
    const ADDRESS_ONE_NOT_AVAILABLE     = 'address not available';

    const HDFC_ECMS_FUND_TRANS_EXPERIMENT_ID = "hdfc_ecms_fund_trans_experiment_id";

    const TRANSFER_TYPE_UPI = "UPI";
    const TRANSFER_TYPE_NEFT = "NEFT";
    const TRANSFER_TYPE_RTGS = "RTGS";
    const TRANSFER_TYPE_IMPS = "IMPS";
    const TRANSFER_TYPE_FT = "FT";
    const TRANSFER_TYPE_IFT = "IFT";
    const TRANSFER_TYPE_TRANSFER = "TRANSFER";

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

    const ENABLE = 'enable';

    const TXN_CREATED_FIRE_WEBHOOK_SYNC = 'app.transaction_created_fire_webhook_sync';

    const CREDIT_ACCOUNT_NUMBER = 'creditAccountNumber';

    // storing the mapping of IDFC product code to mode for fund loading va callbacks
    const IDFC_PRODUCT_CODE_TO_MODE_MAPPING = [
        'INEFT' => Mode::NEFT,
        'IRTGS' => Mode::RTGS,
        'IIMPS' => Mode::IMPS,
        'IIFT'  => Mode::IFT,
    ];

    // Sensitive data for VA IDFC callback. Commenting as we need it now for testing purpose
    const SENSITIVE_DATA_FOR_VA_IDFC_CALLBACK = [
//        'remitterAc',
//        'VANum',
//        'poolingAccountNumber',
//        'vaNumber',
//        'remitterAccountNumber'
    ];

    const COLLECTX_BANK_TRANSFER_MODES = [
        self::TRANSFER_TYPE_NEFT,
        self::TRANSFER_TYPE_RTGS,
        self::TRANSFER_TYPE_IMPS,
        self::TRANSFER_TYPE_FT,
        self::TRANSFER_TYPE_IFT,
        self::TRANSFER_TYPE_TRANSFER,
    ];

    const COLLECTX_UPI_TRANSFER_MODES = [
        self::TRANSFER_TYPE_UPI,
    ];

}
