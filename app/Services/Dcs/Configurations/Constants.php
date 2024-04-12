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
    const CountryDashboardConfigurations = 'country_dashboard_config';

    const OrgDefaultIIR = 'default_iir_config';

    const DisableCaptcha = 'disable_captcha';
    const DashboardCaptchaEntityId = "ALL";
    const CustomUDFFlagConfig = "CustomUDFFlagConfig";

    const UpiInAppDisplayControls = "upi_in_app_display_controls";
    const UpiInAppPrefetch        = "upi_in_app_prefetch";
    const UpiInAppRewardConfigs   = "upi_in_app_rewards_configs";
    const NcOptOutConfiguration   = 'nc_opt_out_configuration';

    const AccountingIntegrationConfig = 'accounting_integration_config';

    const DCCCurrencyLevelMarkup = 'dcc_currency_level_markup';
    const CurrencyLevelMarkups = 'currency_level_markups';

    const DisabledCardCurrencies = 'disabled_card_currencies';

    const PaymentNotesKeyColumns = 'payment_notes_key_columns';

    const RectangularLogoUrl = 'rectangular_logo_url';

    /**
     * Stores the mapping of the configurations to their corresponding dcs keys
     */
    public static $configurationsToDCSKeyMapping = [
        self::EmandateMerchantConfigurations => "rzp/pg/merchant/emandate/DebitConfiguration",
        self::NetbankingConfigurations       => "rzp/pg/merchant/netbanking/banking_program/NetBankingConfiguration",
        self::CustomHardLimitConfigurations  => "rzp/pg/org/onboarding/banking_program/Config",
        self::DirectAccountsPayoutModeConfig => "rzp/x/merchant/payouts/direct_accounts/PayoutModeConfig",
        self::DisableCaptcha                 => "rzp/platform/dashboard/authentication/Captcha",
        self::OrgDefaultIIR                  => "rzp/pg/org/admindashboard/banking_program/InstrumentRequest",
        self::CustomUDFFlagConfig            => "rzp/pg/org/cards/banking_program/CardsConfig",
        self::UpiInAppDisplayControls        => "rzp/pg/merchant/upi/in_app/DisplayControls",
        self::NcOptOutConfiguration          => "rzp/platform/partner/optout/NeedsClarification",
        self::CountryDashboardConfigurations => "rzp/pg/country/dashboard/merchant/UIControls",
        self::AccountingIntegrationConfig    => "rzp/x/merchant/accounting/IntegrationSettings",
        self::DCCCurrencyLevelMarkup         => "rzp/pg/merchant/cross_border/india/DCCConfig",
        self::UpiInAppPrefetch               => "rzp/pg/merchant/upi/in_app/Prefetch",
        self::UpiInAppRewardConfigs         => "rzp/pg/merchant/upi/in_app/RewardConfigs",
        self::PaymentNotesKeyColumns         => "rzp/pg/merchant/dashboard/banking_program/UIControls",
        self::RectangularLogoUrl             => "rzp/pg/merchant/onboarding/banking_program/MerchantConfigDetails",
        self::DisabledCardCurrencies         => "rzp/pg/org/cross_border/Currency",
    ];

}
