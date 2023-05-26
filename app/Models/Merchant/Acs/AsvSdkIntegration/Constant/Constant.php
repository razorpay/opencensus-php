<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant;

class Constant
{
    const ASV_SDK_CLIENT = 'asv_sdk_client';
    const ASV_CONFIG = 'applications.asv_v2';
    const GRPC_HOST = 'grpc_host';
    const USERNAME = 'username';
    const PASSWORD = 'password';
    const GRPC_TIMEOUT = 'grpc_timeout';

    const TRACE   = 'trace';

    // timeouts
    const TIMEOUT_500MS = 500000;

    // splitz experiment names
    CONST SPLITZ_WEBSITE_READ_MERCHANTID = "splitz_experiment_website_read_merchantid";
    CONST SPLITZ_WEBSITE_READ_FIND = "splitz_experiment_website_read_find";

    // function identifiers
    CONST GET_WEBSITE_BY_MERCHANT_ID = "ASV_MERCHANT_WEBSITE_getWebsiteDetailsForMerchantId";
    CONST MERCHANT_WEBSITE_FIND = "ASV_MERCHANT_WEBSITE_find";
}
