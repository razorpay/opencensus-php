<?php

namespace RZP\Models\Merchant\Detail\NeedsClarification;

class Constants
{
    const FIELD_NAME                        = 'field_name';
    const FIELD_VALUE                       = 'field_value';
    const FIELD_TYPE                        = 'field_type';
    const TEXT                              = 'text';
    const DOCUMENT                          = 'document';
    const REASON_CODE                       = 'reason_code';
    const REASON                            = 'reason';
    const REASON_DESCRIPTION                = 'reason_description';
    const FIELDS                            = 'fields';
    const DOCUMENTS                         = 'documents';
    const DISPLAY_NAME                      = 'display_name';
    const REASON_MAPPING                    = 'reason_mapping';
    const ACKNOWLEDGED                      = 'acknowledged';

    // needs clarification field names
    const PROMOTER_PAN                      = 'promoter_pan';
    const BUSINESS_PAN_NAME                 = 'business_pan_name';
    const BANK_ACCOUNT_NUMBER               = 'bank_account_number';
    const PERSONAL_PAN_IDENTIFIER           = 'personal_pan_identifier';
    const COMPANY_PAN_IDENTIFIER            = 'company_pan_identifier';
    const POA_DOC                           = 'poa_doc';
    const GSTIN_IDENTIFER                   = 'gstin_identifier';
    const CIN_IDENTIFER                     = 'cin_identifier';
    const LLPIN_IDENTIFIER                  = 'llpin_identifier';
    const SHOP_ESTABLISHMENT_IDENTIFIER     = 'shop_establishment_identifier';

    const IS_SIGNATORY_NAME_MATCHED         = 'isSignatoryNameMatched';
    const IS_COMPANY_NAME_MATCHED           = 'isCompanyNameMatched';
    const IS_SIGNATORY_VALIDATED            = 'is_signatory_validated';
    const IS_ARTEFACT_VALIDATED             = 'is_artefact_validated';
    const ADDITIONAL_DETAILS                = 'additional_Details';
    const RELATED_FIELDS                    = 'related_fields';
    const CAN_RF_EXIST_INDEPENDENTLY        = 'can_rf_exist_independently';
    /* CAN_RF_EXIST_INDEPENDENTLY signifies whether related fields
    can exist as an independent clarification query or not
     * */
    const BUSINESS_TYPE_REASON_CODE_MAPPING = 'business_type_reason_code_mapping';
    const REASON_TYPE                       = 'reason_type';

    const CONTACT_MOBILE                    = 'contact_mobile';

    const DEDUPE_CHECK_KEY                  = 'dedupe_check_key';
}
