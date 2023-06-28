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

    const ASV_SPLITZ_EXPERIMENT_MERCHANT_EMAIL_READ_BY_MERCHANT_ID = 'splitz_experiment_merchant_email_read_by_merchant_id';
    // function identifiers
    CONST GET_WEBSITE_BY_MERCHANT_ID = "ASV_MERCHANT_WEBSITE_getWebsiteDetailsForMerchantId";

    CONST GET_BUSINESS_DETAIL_BY_MERCHANT_ID = "ASV_MERCHANT_WEBSITE_getBusinessDetailsForMerchantId";
    CONST MERCHANT_WEBSITE_FIND = "ASV_MERCHANT_WEBSITE_find";

    CONST GET_EMAIL_BY_TYPE_AND_MERCHANT_ID = "getByTypeAndMerchantId";

    CONST GET_EMAIL_BY_MERCHANT_ID = 'getEmailByMerchantId';
    const GET_STAKEHOLDER_BY_MERCHANT_ID = 'GET_STAKEHOLDER_BY_MERCHANT_ID';

    const GET_DOCUMENT_BY_ID = 'AsvMerchantDocument_getDocumentById';

    const GET_DOCUMENT_BY_TYPE_AND_MERCHANT_ID = 'AsvMerchantDocument_getDocumentByTypeAndMerchantId';
    const GET_PRIMARY_ADDRESS_FOR_STAKEHOLDER =  'GET_PRIMARY_ADDRESS_FOR_STAKEHOLDER';
}
