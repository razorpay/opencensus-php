<?php


namespace RZP\Models\BankingAccount;


class Constants
{
    const BANKING_ACCOUNT_RESET_WEBHOOK_COMMENT = 'System Comment: Admin is reseting the webhook data and reverting back to the previous state. Bank has to trigger the account opening webhook again.';

    const X_OPS_TEAM = 'ops';

    const BANKING_ACCOUNT_SOURCE_TEAM_OR_TYPE_AS_INTERNAL = 'internal';

    const STATUS_MESSAGE = 'StatusMessage';

    const SUCCESS_MESSAGE = 'Data Successfully Inserted';

    const ERROR = 'error';

    const ERROR_DESC = 'ErrorDesc';

    const ERROR_MESSAGE = 'A schema validation error has occurred while validating the message tree,6008,1,1,213,cvc-minLength-valid: The length of value \"\" is \"0\" which is not valid with respect to the minLength facet with value \"1\" for type \"#Anonymous\".,/Root/XMLNSC/NeoBankingLeadReq/Body/%s';

    // List of cities where Feet on Street is applicable
    const FOS_CITIES = [
        'Central Delhi',
        'Delhi',
        'East Delhi',
        'New Delhi',
        'North Delhi',
        'North East Delhi',
        'North West Delhi',
        'South Delhi',
        'South West Delhi',

        'Mumbai',
        'Navi Mumbai',

        'Bengaluru',
        'Bangalore',
    ];

    const FOS_CITIES_REGEX = [
        'delhi',
        'mumbai',
        'bengaluru',
        'bangalore',
    ];

    const NON_FOS = 'Non_FOS';

    const ACTIVATION_ACCOUNT_TYPE = 'activation_account_type';


    // Serviceability Check consts
    const RBL = 'RBL';

    const ICICI = 'ICICI';

    const ACTIVE = 'active';

    const PINCODE_DETAILS = 'pincode_details';

    const CITY = 'city';

    const STATE = 'state';

    const REGION = 'region';

    const IS_SERVICEABLE = 'is_serviceable';

    const SERVICEABILITY = 'serviceability';

    const PARTNER_BANK = 'partner_bank';

    const POE_NOT_VERIFIED = 'PoE Not Verified';
    const ENTITY_NAME_MISMATCH = 'Entity Name Mismatch';
    const ENTITY_TYPE_MISMATCH = 'Entity Type Mismatch';
    const UNEXPECTED_STATE_CHANGE_LOG = 'Unexpected State Change Log';
    const DUPLICATE_MERCHANT_APPLICATION = 'Application with Duplicate Merchant Name';

    const NAME = 'name';

    const IS_UPI_ALLOWED = 'is_upi_allowed';

    const SKIP  = 'skip';
    const COUNT = 'count';
}
