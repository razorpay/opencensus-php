<?php

namespace RZP\Models\Payout\Configurations\DirectAccounts\PayoutModeConfig;

class Constants
{
    const MERCHANT_ID = 'merchant_id';

    const FIELDS = 'fields';

    const DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG = 'direct_accounts_payout_mode_config';

    const ALLOWED_UPI_CHANNELS = 'allowed_upi_channels';

    const DIRECT_ACCOUNTS_PAYOUT_MODE_CONFIG_FIELDS = [
        self::ALLOWED_UPI_CHANNELS,
    ];
}
