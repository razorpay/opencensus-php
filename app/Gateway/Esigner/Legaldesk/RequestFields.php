<?php

namespace RZP\Gateway\Esigner\Legaldesk;

class RequestFields
{
    // Headers
    const REST_API_KEY   = 'x-parse-rest-api-key';
    const APPLICATION_ID = 'x-parse-application-id';

    // Mandate creation request
    const REFERENCE_ID               = 'reference_id';
    const MANDATE_REQUEST_ID         = 'mandate_request_id';
    const DEBTOR_ACCOUNT_TYPE        = 'debtor_account_type';
    const DEBTOR_ACCOUNT_ID          = 'debtor_account_id';
    const INSTRUCTED_AGENT_ID_TYPE   = 'instructed_agent_id_type';
    const INSTRUCTED_AGENT_ID        = 'instructed_agent_id';
    const INSTRUCTED_AGENT_ID_CODE   = 'instructed_agent_id_code';
    const INSTRUCTED_AGENT_NAME      = 'instructed_agent_name';
    const INSTRUCTED_AGENT_CODE      = 'instructed_agent_code';
    const OCCURANCE_SEQUENCE_TYPE    = 'occurance_sequence_type';
    const OCCURANCE_FREQUENCY_TYPE   = 'occurance_frequency_type';
    const SCHEME_REFERENCE_NUMBER    = 'scheme_reference_number';
    const CONSUMER_REFERENCE_NUMBER  = 'consumer_reference_number';
    const DEBTOR_NAME                = 'debtor_name';
    const EMAIL_ADDRESS              = 'email_address';
    const FIRST_COLLECTION_DATE      = 'first_collection_date';
    const FINAL_COLLECTION_DATE      = 'final_collection_date';
    const PHONE_NUMBER               = 'phone_number';
    const MOBILE_NUMBER              = 'mobile_number';
    const COLLECTION_AMOUNT_TYPE     = 'collection_amount_type';
    const AMOUNT                     = 'amount';
    const OTHER_REFERENCE            = 'other_reference';
    const TIME_STAMP                 = 'time_stamp';
    const MANDATE_TYPE_CATEGORY_CODE = 'mandate_type_category_code';
    const ESIGN_TYPE                 = 'esign_type';
    const CALLBACK_URL               = 'callback_url';
    const INSTRUCTING_AGENT_NAME     = 'instructing_agent_name';
    const INSTRUCTING_AGENT_ID       = 'instructing_agent_id';
    const CREDITOR_NAME              = 'creditor_name';
    const CREDITOR_ACCOUNT_ID        = 'creditor_account_id';
    const AUTHENTICATION_MODE        = 'authentication_mode';
    const IS_UNTIL_CANCELLED         = 'is_until_cancel';

    const EMANDATE_ID = 'emandate_id';
}
