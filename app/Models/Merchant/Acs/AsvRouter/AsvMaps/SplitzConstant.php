<?php

namespace RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
final class SplitzConstant
{
    const SPLITZ_WEBSITE_READ_FIND = "splitz_experiment_website_read_find";
    const SPLITZ_EMAIL_GET_BY_MERCHANT_ID = 'splitz_experiment_merchant_email_read_by_merchant_id';

    const SPLITZ_EMAIL_GET_BY_TYPE_AND_MERCHANT_ID = 'splitz_experiment_merchant_email_read_by_type_and_merchant_id';

    const SPLITZ_EMAIL_GET_BY_ID = 'splitz_experiment_merchant_email_read_by_id';

    const SPLITZ_BUSINESS_DETAIL_GET_BY_MERCHANT_ID = 'splitz_experiment_merchant_business_detail_read_by_merchant_id';
    const SPLITZ_BUSINESS_DETAIL_GET_BY_ID = 'splitz_experiment_merchant_business_detail_read_by_id';

    const SPLITZ_DOCUMENT_GET_BY_TYPE_AND_MERCHANT_ID = 'splitz_experiment_merchant_document_read_by_type_and_merchant_id';

    const SPLITZ_DOCUMENT_GET_BY_ID = 'splitz_experiment_merchant_document_read_by_id';

    const SPLITZ_STAKEHOLDER_GET_BY_ID = 'splitz_experiment_stakeholder_read_by_id';
    const SPLITZ_STAKEHOLDER_GET_BY_MERCHANT_ID = 'splitz_experiment_stakeholder_read_by_merchant_id';

    const SPLITZ_ADDRESS_GET_BY_STAKEHOLDER_ID = 'splitz_experiment_address_read_by_stakeholder_id';

    const SPLITZ_MERCHANT_DETAIL_GET_BY_ID = 'splitz_experiment_merchant_detail_read_by_id';

    const SPLITZ_MERCHANT_GET_BY_ID = 'splitz_experiment_merchant_read_by_id';

    const SPLITZ_SEND_WRITE_ROUTE_OR_WORKER_TO_ASV = 'splitz_experiment_send_write_route_or_worker_to_asv';

    const SPLITZ_MERCHANT_WEBSITE_SAVE_OR_FAIL  = 'splitz_experiment_merchant_website_save_or_fail';

    const SPLITZ_MERCHANT_EMAIL_SAVE_OR_FAIL  = 'splitz_experiment_merchant_email_save_or_fail';

    const SPLITZ_IMPLICIT_JOIN_ENTITY = 'splitz_experiment_implicit_join_entity';

    const SPLITZ_IMPLICIT_JOIN_WEBSITE_BY_MERCHANTID = 'splitz_experiment_implicit_join_website_by_merchant_id';

    const SPLITZ_IMPLICIT_JOIN_BUSINESS_DETAIL_BY_MERCHANTID = 'splitz_experiment_implicit_join_business_detail_by_merchant_id';

    const SPLITZ_MERCHANT_DETAIL_FIND_FOR_IMPLICIT_JOIN = 'splitz_experiment_merchant_detail_find_for_implicit_join';

    const SPLITZ_MERCHANT_FIND_FOR_IMPLICIT_JOIN = 'splitz_experiment_merchant_find_for_implicit_join';

    const SPLITZ_IMPLICIT_JOIN_STAKEHOLDER_BY_MERCHANTID = 'splitz_experiment_implicit_join_stakeholder_by_merchant_id';

    const SPLITZ_IMPLICIT_JOIN_DOCUMENT_BY_MERCHANTID = 'splitz_experiment_implicit_join_document_by_merchant_id';

    const SPLITZ_MERCHANT_BUSINESS_DETAIL_SAVE_OR_FAIL = 'splitz_experiment_merchant_business_detail_save_or_fail';
}
