<?php

namespace RZP\Models\Payout\Batch;

class Constants
{
    const REFERENCE_ID = Entity::REFERENCE_ID;

    const BATCH_REFERENCE_ID = 'batch_reference_id';

    const PAYOUTS = 'payouts';

    const CORRELATION_ID = 'correlation_id';

    const BATCH_STATUS = 'batch_status';

    const EXTENSION_CSV = 'csv';

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

}
