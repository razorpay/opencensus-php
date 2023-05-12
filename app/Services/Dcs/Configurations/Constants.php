<?php

namespace RZP\Services\Dcs\Configurations;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Admin\Service as AdminService;
use RZP\Models\Feature\Constants as APIFeaturesConstants;
use RZP\Services\Dcs\Features\Constants as DcsConstants;

class Constants
{
    const EmandateMerchantConfigurations = 'emandate_merchant_configurations';

    const NetbankingConfigurations = 'netbanking_configurations';

    const CustomHardLimitConfigurations = "custom_hard_limit_configurations";

    const DirectAccountsPayoutModeConfig = 'direct_accounts_payout_mode_config';

    /**
     * Stores the mapping of the configurations to their corresponding dcs keys
     */
    public static $configurationsToDCSKeyMapping = [
        self::EmandateMerchantConfigurations => "rzp/pg/merchant/emandate/DebitConfiguration",
        self::NetbankingConfigurations       => "rzp/pg/merchant/netbanking/banking_program/NetBankingConfiguration",
        self::CustomHardLimitConfigurations  => "rzp/pg/org/onboarding/banking_program/Config",
        self::DirectAccountsPayoutModeConfig => "rzp/x/merchant/payouts/direct_accounts/PayoutModeConfig",
    ];

}
