<?php


namespace RZP\Models\Admin\AdminLead;

use RZP\Models\Feature\Constants as FeatureConstants;

class Constants
{

    const REGULAR_TEST_MERCHANT   = "Regular Test Merchant";
    const OPTIMIZER_ONLY_MERCHANT = "Optimizer Only Merchant";
    const DS_ONLY_MERCHANT        = "DS Only Merchant";

    const MERCHANT_TYPE = 'merchant_type';
    const TOKEN_DATA    = 'token_data';
    const FORM_DATA     = 'form_data';

    const ALLOWED_MERCHANT_TYPE_FEATURE_MAPPING = [
        self::DS_ONLY_MERCHANT        => [FeatureConstants::ONLY_DS],
        self::OPTIMIZER_ONLY_MERCHANT => [FeatureConstants::OPTIMIZER_ONLY_MERCHANT],
        self::REGULAR_TEST_MERCHANT   => [FeatureConstants::REGULAR_TEST_MERCHANT]
    ];
}
