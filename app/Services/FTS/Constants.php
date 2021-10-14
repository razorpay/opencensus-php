<?php

namespace RZP\Services\FTS;

final class Constants
{
    const ID                             = 'id';

    const VPA                            = 'vpa';

    const CARD                           = 'card';

    const CODE                           = 'code';

    const NAME                           = "name";

    const TYPE                           = 'type';

    const MODE                           = 'mode';

    const BODY                           = 'body';

    const HANDLE                         = 'handle';

    const AMOUNT                         = 'amount';

    const STATUS                         = 'status';

    const PAYOUT                         = 'payout';

    const SAVING                         = 'saving';

    const REFUND                         = 'refund';

    const CHANNEL                        = 'channel';

    const CONFIGURATION                  = 'configuration';

    const MESSAGE                        = 'message';

    const ACCOUNT                        = 'account';

    const TRANSFER                       = 'transfer';

    const PRODUCT                        = 'product';

    const USERNAME                       = 'username';

    const ON_DEMAND                      = 'on_demand';

    const IFSC_CODE                      = 'ifsc_code';

    const NARRATION                      = 'narration';

    const ENTITY_ID                      = 'entity_id';

    const SOURCE_ID                      = 'source_id';

    const MODE_IMPS                      = 'IMPS';

    const SETTLEMENT                     = 'settlement';

    const ISSUER_BANK                    = 'issuer_bank';

    const VAULT_TOKEN                    = 'vault_token';

    const SOURCE_TYPE                    = 'source_type';

    const CREDENTIALS                    = 'credentials';

    const TRANSFER_BY                    = 'transfer_by';

    const INITIATE_AT                    = 'initiate_at';

    const MERCHANT_ID                    = 'merchant_id';

    const NETWORK_CODE                   = 'network_code';

    const BANK_ACCOUNT                   = 'bank_account';

    const ACCOUNT_TYPE                   = 'account_type';

    const PAYOUT_REFUND                  = 'payout_refund';

    const PENNY_TESTING                  = 'penny_testing';

    const PREFERRED_MODE                 = 'preferred_mode';

    const STATUS_CREATED                 = 'created';

    const ACCOUNT_NUMBER                 = 'account_number';

    const INTERNAL_ERROR                 = 'internal_error';

    const FUND_ACCOUNT_ID                = 'fund_account_id';

    const DEFAULT_CHANNEL                = 'default_channel';

    const BANKING_ACCOUNT                = 'banking_account';

    const BENEFICIARY_PIN                = 'beneficiary_pin';

    const STATUS_INITIATED               = 'initiated';

    const BENEFICIARY_NAME               = 'beneficiary_name';

    const BENEFICIARY_CODE               = 'beneficiary_code';

    const BENEFICIARY_CITY               = 'beneficiary_city';

    const FUND_TRANSFER_ID               = 'fund_transfer_id';

    const PREFERRED_CHANNEL              = 'preferred_channel';

    const BENEFICIARY_EMAIL              = 'beneficiary_email';

    const BENEFICIARY_STATE              = 'beneficiary_state';

    const MOZART_IDENTIFIER              = 'mozart_identifier';

    const IMPS_CUTOFF_AMOUNT             = 500000;

    const BENEFICIARY_MOBILE             = 'beneficiary_mobile';

    const IS_VIRTUAL_ACCOUNT             = 'is_virtual_account';

    const BENEFICIARY_ADDRESS            = 'beneficiary_address';

    const BENEFICIARY_COUNTRY            = 'beneficiary_country';

    const RTGS_CUTOFF_HOUR_MIN           = 8;

    const BENEFICIARY_REQUIRED          = 'beneficiary_required';

    const FUND_ACCOUNT_VALIDATION        = 'fund_account_validation';

    const PREFERRED_SOURCE_ACCOUNT_ID    = 'preferred_source_account_id';

    const RTGS_REVISED_CUTOFF_HOUR_MAX   = 17;

    const RTGS_REVISED_CUTOFF_MINUTE_MAX = 30;

    const NEFT_CUTOFF_HOUR_MIN           = 8;

    const NEFT_CUTOFF_HOUR_MAX           = 18;

    const NEFT_CUTOFF_MINUTE_MAX         = 15;

    const BENEFICIARY_STATUS             = 'beneficiary_status';

    const COMPLETED                      = 'COMPLETED';

    const VALIDATION_ERROR               = 'VALIDATION_ERROR';

    const STATUS_FAILED                  = 'failed';

    const ES_ON_DEMAND                   = 'ES_ON_DEMAND';

    const CA_PAYOUT                      = 'CA_PAYOUT';

    const IS_BATCH                       = 'is_batch';

    const BANK_STATUS_CODE               = 'bank_status_code';

    const GATEWAY_ERROR_CODE             = 'gateway_error_code';

    const REMARKS                        = 'remarks';

    const UTR                            = 'utr';

    const RETURN_UTR                     = 'return_utr';

    const FAILURE_REASON                 = 'failure_reason';

    const SOURCE_ACCOUNT_ID              = 'source_account_id';

    const DEFAULT_SOURCE                 = "fts.channel.notify";

    const SOURCE                         = 'source';

    const PAYOUT_DOWNTIME                = 'payout.downtime';

    const PAYOUT_DOWNTIME_PREFIX         = 'poutdown_';

    const SOURCE_ACCOUNT_TYPE_IDENTIFIER = 'account_type';

    const COUNTRY_CODE                   = 'country_code';

    const WALLET                         = 'wallet';

    const PROVIDER                       = 'provider';

    const WALLET_TRANSFER_MODE_FTS       = 'WALLET_TRANSFER';

    const FTS_AMAZON_PAY_CHANNEL         = 'amazon_pay';

    const BANK_ACCOUNT_TYPE              = 'bank_account_type';

    const REQUEST_META                   = "request_meta";

    const PAYOUT_NOTES                   = "payout_notes";

    const CONTACT_NOTES                  = "contact_notes";

    const BENEFICIARY_BANK_NAME          = "beneficiary_bank_name";

    public static function getProducts(): array
    {
        return [
            Constants::PAYOUT,
            Constants::SETTLEMENT,
            Constants::REFUND,
            Constants::PAYOUT_REFUND,
            Constants::PENNY_TESTING,
            Constants::ES_ON_DEMAND,
            Constants::CA_PAYOUT,
        ];
    }
}
