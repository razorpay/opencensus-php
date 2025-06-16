<?php


namespace RZP\Models\Payout;

use RZP\Models\Bank\BankCodes;
use RZP\Models\Settlement\Channel;

class Constants
{
    // Constants for Download attachments in Payout reports

    public const ZIP_FILE_ID                = 'zip_file_id';

    public const PAYOUT_ATTACHMENT_PREFIX   = 'Payouts';

    public const PAYOUT_ATTACHMENTS         = 'payout_attachments';

    public const SIGNED_URL                 = 'signed_url';

    public const STATUS                     = 'status';

    public const MERCHANT_EMAIL             = 'merchant_email';

    public const SUBJECT                    = 'subject';

    public const TEMPLATE_NAME              = 'template_name';

    public const DATA                       = 'data';

    public const EMAILS                     = 'emails';

    public const MERCHANT_ID                = 'merchant_id';

    public const MERCHANT_NAME              = "merchant_name";

    public const MERCHANT_PAN               = "merchant_pan";

    public const MERCHANT_DETAIL            = "merchant_detail";

    public const MERCHANT_ADDRESS           = "merchant_address";

    const REMITTER_DETAILS                  = 'remitter_details';

    public const ATTACHMENT_FILE_URL        = 'attachment_file_url';

    public const PAYOUT_ATTACHMENT_METRO_TOPIC = 'payout_attachments';

    public const DISPLAY_NAME               = 'display_name';

    public const EXTENSION                  = 'extension';

    public const MIME                       = 'mime';

    public const SEND_EMAIL                 = 'send_email';

    public const RECEIVER_EMAIL_IDS         = 'receiver_email_ids';

    public const FILE_UPLOAD_FAILED         = 'failed';

    public const FILE_UPLOADED              = 'uploaded';

    public const STATUS_CODE                = 'status_code';

    public const ACCOUNT_NUMBERS            = 'account_numbers';

    public const MIGRATION_REDIS_SUFFIX     = 'migration_';

    public const MIGRATION_MUTEX_RETRY_COUNT = 1;

    public const BULK_TEMPLATE_CONFIG_KEY   = 'BULK_PAYOUT_%s_%s_%s';

    public const PAYOUT_ID                  = 'payout_id';

    // Fund Management Payout Constants

    const THRESHOLDS                      = 'thresholds';
    const ALL_MERCHANTS                   = 'all_merchants';
    const NEFT_THRESHOLD                  = 'neft_threshold';
    const LITE_BALANCE_THRESHOLD          = 'lite_balance_threshold';
    const LITE_DEFICIT_ALLOWED            = 'lite_deficit_allowed';
    const FMP_CONSIDERATION_THRESHOLD     = 'fmp_consideration_threshold';
    const TOTAL_AMOUNT_THRESHOLD          = 'total_amount_threshold';
    const FUND_MANAGEMENT_PAYOUT          = 'fund_management_payout';
    const PAYOUT_CREATE_INPUT             = 'payout_create_input';
    const FMP_UNIQUE_IDENTIFIER           = 'fmp_unique_identifier';
    const DESTINATION_CHANNEL             = 'destination_channel';
    const DESTINATION_TYPE                = 'destination_type';

    public const FMP_DESTINATION_CHANNEL_TO_IFSC_MAP = [
        Channel::YESBANK => "YESB0000022",
        Channel::AXIS    => "UTIB0001506",
        Channel::IDFC    => "IDFB0080151",
        Channel::RBL     => "RATN0000091",
        Channel::ICICI   => BankCodes::IFSC_ICIC,
    ];

    const FUND_MANAGEMENT_PAYOUT_INITIATE = 'worker:fund_management_payout_initiate';

    // Fund Management Payout Error Descriptions
    const LITE_BALANCE_IS_ABOVE_THRESHOLD = 'Lite balance is above threshold.';
    const CA_BALANCE_NOT_ENOUGH_FOR_FMP   = 'CA balance not enough to initiate FMP';
    const INVALID_OFFSET_AMOUNT_FOR_FMP   = 'Offset amount is less than equal to zero.';

    public const MERCHANT_EXCLUSION_FOR_ON_HOLD_PAYOUT_CA =  [
        "KQmiOStxMbJRKD", "KQmqfUjixRdKwd", "KQWFyOWq9SwLPz", "KQmiOStxMbJRKD", "CFATEjs18VKdtr", "KQWFyOWq9SwLPz", "KQmqfUjixRdKwd",
        "LBX3zrXRQD20QS", "LBWwHD7BTMIToy", "LBYQxFVIPdk1A3", "KQtWekXcgwDObj", "CEguwEip3eDPfV", "KQtR99FZetQe87"
    ];

    const RAZORPAYX_LITE                    = 'RazorpayX Lite';

    const CHANNEL_AXIS_BANK                      = 'Axis Bank';

    const CHANNEL_IDFC_BANK                      = 'IDFC Bank';

    const CHANNEL_ICICI_BANK                     = 'ICICI Bank';

    const CHANNEL_RBL_BANK                       = 'RBL Bank';

    const CHANNEL_YES_BANK                       = 'Yes Bank';

    public const ACCOUNT_TYPE               = 'account_type';

    public const CHANNEL                    = 'channel';

    public const MODE                       = 'mode';

    public const INCLUDE_MERCHANT_IDS       = 'include_merchant_ids';

    public const EXCLUDE_MERCHANT_IDS       = 'exclude_merchant_ids';

    // Payout Smart Routing Constants

    const BALANCE                           = 'balance';
    const BALANCE_ENTITY                    = 'balance_entity';

    // Charge collection constants
    const PRODUCT_ID    = "product_id";
    const CHARGE_ID     = "charge_id";

    const PAYOUT_FEE_TAX_PERCENTAGE         = 18;
    const TAX                               = 'tax';

    //Constants for account statement source event queue
    const ENTITY_ID           = "entity_id";
    const ENTITY_TYPE         = "entity_type";
    const UTR                 = "utr";
    const PAYOUTS_ENTITY_TYPE = "payout";

    const EVENT_CREATED_TIMESTAMP = "event_create_timestamp";
    const EVENT_ID                = "event_id";
    const GATEWAY_REF_NO          = "gateway_ref_no";
    const CMS_REF_NO              = "cms_ref_no";
    const AMOUNT                  = 'amount';
    const BALANCE_ID              = 'balance_id';
    const PROCESS_TYPE_KAFKA_EVENT_VIA_PS = "kafka_event_via_ps";
    const NAME_MATCHING_THRESHOLD_DEFAULT = 75;
    const CONFIG_VALUE_SETTINGS = 'config_value';
    const PHONE_NUMBER_PAYOUTS = 'phone_number_payout';
    const PAYEE_IFSC = 'payee_ifsc';
    const PAYEE_BANK_NAME = 'payee_bank_name';
    const FTA = 'fta';
}
