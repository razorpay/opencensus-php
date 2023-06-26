<?php

namespace RZP\Models\BankingAccountService;

class Constants
{
    const ID                            = 'id';

    const DATA                          = 'data';

    const ACCOUNT_NUMBER                = 'account_number';

    const CHANNEL                       = 'channel';

    const MERCHANT_ID                   = 'merchant_id';

    const EMAIL_ID                      = 'email_id';

    const PHONE_NUMBER                  = 'phone_number';

    const CONSTITUTION                  = 'constitution';

    const BALANCE_ID                    = 'balance_id';

    const BUSINESS_ID                   = 'business_id';

    // Credentials fields
    const CORP_ID                       = 'CrpId';

    const CORP_USER                     = 'CrpUsr';

    const URN                           = 'URN';

    const STATUS                        = 'status';

    const BUSINESS_PATH                 = 'business';

    const APPLICATIONS_PATH             = 'applications';

    const EXPAND_DOCUMENTS              = '?expand_documents=true';

    const PERSON_PATH                   = 'person';

    const SIGNATORIES                   = 'signatories';

    const SIGNATORY_TYPE                = 'signatory_type';

    const PERSON_ID                     = 'person_id';

    const PERSON                        = 'person';

    const APPLICATION_SPECIFIC_FIELDS   = 'application_specific_fields';

    const DOCUMENT                      = 'document';

    const ID_PROOF                      = 'idProof';

    const ADDRESS_PROOF                 = 'addressProof';

    const PERSONS_DOCUMENT_MAPPING      = 'persons_document_mapping';

    const BAS_PIN_CODE_SERVICEABILITY   = 'is_serviceable';

    const BAS_SERVICEABILITY_BULK   = 'is_serviceable_bulk';

    const BAS_CHECK_SERVICEABILITY  = 'check_serviceability';

    const ALLOCATE_LEAD                 = "allocate_lead";

    const SIGNATORY_PATH                = 'signatory';

    const SIGNATORY_ID                  = 'signatory_id';

    const APPLICATION_ID                = 'application_id';

    const APPLICATION_PATH              = 'application';

    const ADMIN_BANKING_ACCOUNT_APPLY_PATH  = 'admin/apply';

    const CA_PARTNER_BANK               = 'CA_Partner_Bank';

    const CA_PREFERRED_EMAIL            = 'CA_Preferred_Email';

    const CA_PREFERRED_PHONE            = 'CA_Preferred_Phone';

    const X_ONBOARDING_CATEGORY         = 'x_onboarding_category';

    const CAMPAIGN_ID                   = 'Campaign_ID';

    const PRODUCT_NAME                  = 'product_name';

    const SOURCE                        = 'source';

    const SOURCE_DETAIL                 = 'source_detail';

    const X_DASHBOARD                   = 'x_dashboard';

    const RBL                           = 'RBL';

    const ICICI                         = 'ICICI';

    const X_CA_UNIFIED                  = 'X-CA-Unified';

    const X_CA_UNIFIED_NITRO            = 'X-CA-Unified-Nitro';

    const CURRENT_ACCOUNT               = 'Current_Account';

    const CA_CHANNEL_NITRO              = 'NITRO';

    const BUSINESS                      = 'business';
    const BANKING_ACCOUNT_APPLICATION   = 'banking_account_application';

    const ACCOUNT_MANAGERS              = 'account_managers';
    const SALES_POC                     = 'sales_poc';
    const OPS_POC                       = 'ops_poc';
    const BANK_POC                      = 'bank_poc';
    const OPS_MX_POC                    = 'ops_mx_poc';
    const RZP_ADMIN_ID                  = 'rzp_admin_id';
    const TEAM                          = 'team';
    const NAME                          = 'name';
    const EMAIL                         = 'email';
    const BOOKING_COUNT                 = 'booking_count';

    // RBL Credentials Fields
    const CREDENTIALS                   = 'credentials';
    const LDAP_ID                       = 'ldap_id';
    const LDAP_PASSWORD                 = 'ldap_password';
    const CORP_ID_CRED                  = 'corp_id';
    const CLIENT_ID                     = 'client_id';
    const CLIENT_SECRET                 = 'client_secret';
    const DEV_PORTAL_PASSWORD           = 'dev_portal_password';

    const ACCOUNT_MANAGER_NAME          = 'account_manager_name';

    const ACCOUNT_MANAGER_EMAIL         = 'account_manager_email';

    const ACCOUNT_MANAGER_PHONE         = 'account_manager_phone';

    const CA_ONBOARDING_FLOW            = 'ca_onboarding_flow';

    const SELF_SERVE                    = 'self_serve';

    const BANKING_ACCOUNT               = 'banking_account';

    const NOTIFICATION_TYPE             = 'notification_type';

    const NOTIFICATION_TYPE_X_PRO_ACTIVATION = 'x_pro_activation';

    const VALIDATOR_OP                       = 'validator_op';

    const NOTIFICATION_TYPE_STATUS_CHANGE    = 'status_change';

    const BANKING_ACCOUNT_STATUS_CHANGED     = 'banking_account_status_changed';

    const BANKING_ACCOUNT_SUB_STATUS_CHANGED = 'banking_account_sub_status_changed';

    const TYPE                          = 'type';

    const PARTNER_BANK                  = 'partner_bank';

    const SEARCH_LEADS_API_TO_BAS_QUERY_PARAM_MAPPING = [
        'merchant_business_name' => 'merchant_name',
        'status'                 => 'application_status',
        'bank_reference_number'  => 'application_tracking_id',
        'business_category'      => 'business_type',
        'merchant_email'         => 'email_id',
        'reviewer_id'            => 'ops_poc_id',
        'sales_team'             => 'sales_team_name',
    ];

    const API_TO_BAS_ACCOUNT_TYPE_MAPPING = [
        'current' => 'CA_DIRECT',
    ];

    const RBL_ONBOARDING_APPLICATION    = 'RBL_ONBOARDING_APPLICATION';

    const APPLICATION_TYPE              = 'application_type';

    const PERSON_DETAILS                = 'person_details';

    const PINCODE                       = 'pincode';

    const REGISTERED_ADDRESS_DETAILS    = 'registered_address_details';

    const ADDRESS_PIN_CODE              = 'address_pin_code';
}
