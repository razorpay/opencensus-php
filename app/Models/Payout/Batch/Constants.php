<?php

namespace RZP\Models\Payout\Batch;

use RZP\Models\Payout\Batch\Constants as BatchPayoutConstants;

class Constants
{
    const REFERENCE_ID = Entity::REFERENCE_ID;

    const BATCH_REFERENCE_ID = 'batch_reference_id';

    const PAYOUTS = 'payouts';

    const CORRELATION_ID = 'correlation_id';

    const BATCH_STATUS = 'batch_status';

    const EXTENSION_CSV = 'csv';

    const BATCH_ID              = 'batch_id';

    const BATCH_NAME            = 'batch_name';

    const USER_NAME             = 'user_name';

    const TOTAL_AMOUNT          = 'total_amount';

    const TOTAL_COUNT           = 'total_count';

    const SOURCE_ACCOUNT        = 'source_account';

    const CURRENT_TIME          = 'current_time';

    const BATCH_CREATED_AT      = 'batch_created_at';

    const PAYOUT_STATUS_COUNT   = 'payout_status_count';

    const CREATED_AT            = 'created_at';

    const CREATOR_ID            = 'creator_id';

    const PROCESSED_AMOUNT      = 'processed_amount';

    const ACCOUNT_NUMBER        = 'account_number';

    const CONFIG                = 'config';

    const NAME                  = 'name';

    const PAYOUTS_BATCH_NAMESPACE                       = 'payouts_batch';

    const PAYOUTS_BATCH_REMINDERS_CALLBACK_URL          = 'payouts/batch/%s/merchant/%s/summary/email';

    const PAYOUTS_BATCH_REMINDERS_CALLBACK_TIME         = 120; // 2 hours

    const BANK_TRANSFER_WITH_BENE_ID_BATCH_TYPE         = 'payouts_bank_transfer_bene_id';

    const BANK_TRANSFER_WITH_BENE_DETAILS_BATCH_TYPE    = 'payouts_bank_transfer_bene_details';

    const UPI_WITH_BENE_DETAILS_BATCH_TYPE              = 'payouts_upi_bene_details';

    const UPI_WITH_BENE_ID_BATCH_TYPE                   = 'payouts_upi_bene_id';

    const AMAZONPAY_WITH_BENE_ID_BATCH_TYPE             = 'payouts_amazonpay_bene_id';

    const AMAZONPAY_WITH_BENE_DETAILS_BATCH_TYPE        = 'payouts_amazonpay_bene_details';

    const PAYOUT_MODE_FILE_HEADER                       = 'Payout Mode';

    const BENE_FA_ID_FILE_HEADER                        = "Beneficiary's Fund Account ID";

    const BENE_UPI_ID_FILE_HEADER                       = "Beneficiary's UPI ID";

    const BENE_FA_ID_WALLET_FILE_HEADER                 = "Beneficiary's Fund Account ID Wallet";

    const BENE_PHONE_NUMBER_AMAZONPAY_FILE_HEADER       = "Beneficiary's Phone No. Linked with Amazon Pay";

    const BENE_NAME_FILE_HEADER                         = "Beneficiary Name";

    const BENE_ACC_NO_FILE_HEADER                       = "Beneficiary's Account Number";

    const IFSC_CODE_FILE_HEADER                         = "IFSC Code";

    const PAYOUT_AMOUNT_FILE_HEADER                     = "Payout Amount";

    const BENE_PHONE_NUMBER_AMAZON_PAY_FILE_HEADER      = "Beneficiary's Phone No. Linked with Amazon Pay";

    const MANDATORY_FILE_HEADERS_FOR_BATCH_TYPE         = [
            BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_ID_BATCH_TYPE => [
                BatchPayoutConstants::BENE_FA_ID_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_MODE_FILE_HEADER
            ],
            BatchPayoutConstants::BANK_TRANSFER_WITH_BENE_DETAILS_BATCH_TYPE => [
                BatchPayoutConstants::BENE_NAME_FILE_HEADER,
                BatchPayoutConstants::BENE_ACC_NO_FILE_HEADER,
                BatchPayoutConstants::IFSC_CODE_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_MODE_FILE_HEADER
            ],
            BatchPayoutConstants::UPI_WITH_BENE_DETAILS_BATCH_TYPE => [
                BatchPayoutConstants::BENE_NAME_FILE_HEADER,
                BatchPayoutConstants::BENE_UPI_ID_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER
            ],
            BatchPayoutConstants::UPI_WITH_BENE_ID_BATCH_TYPE => [
                BatchPayoutConstants::BENE_FA_ID_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER
            ],
            BatchPayoutConstants::AMAZONPAY_WITH_BENE_ID_BATCH_TYPE => [
                BatchPayoutConstants::BENE_FA_ID_WALLET_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER
            ],
            BatchPayoutConstants::AMAZONPAY_WITH_BENE_DETAILS_BATCH_TYPE => [
                BatchPayoutConstants::BENE_NAME_FILE_HEADER,
                BatchPayoutConstants::BENE_PHONE_NUMBER_AMAZON_PAY_FILE_HEADER,
                BatchPayoutConstants::PAYOUT_AMOUNT_FILE_HEADER
            ]
    ];
}
