<?php


namespace RZP\Models\Payout;


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

    public const BULK_TEMPLATE_CONFIG_KEY = 'BULK_PAYOUT_%s_%s_%s';

}
