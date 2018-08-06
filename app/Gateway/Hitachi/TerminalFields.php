<?php

namespace RZP\Gateway\Hitachi;

class TerminalFields
{
    //Request Fields

    //Onboarding request arrays
    const GATEWAY_INPUT     = 'gateway_input';
    const MERCHANT_DETAILS  = 'merchant_details';


    //Onboarding request fields
    const COUNTRY                   = 'country';
    const PROCESS_ID                = 'process_id';
    const CUSTOMER_NO               = 'customer_no';
    const CITY                      = 'city';
    const EXISTING_MERCHANT         = 'existing_merchant';
    const SPONSOR_BANK              = 'sponsor_bank';
    const ACTION_CODE               = 'action_code';
    const MID                       = 'mid';
    const MERCHANT_NAME             = 'merchant_name';
    const S_NO                      = 'S_no';
    const SUPER_MID                 = 'super_mid';
    const MERCHANT_GROUP            = 'merchant_group';
    const MERCHANT_STATUS           = 'merchant_status';
    const MCC                       = 'mcc';
    const TID                       = 'tid';
    const CURRENCY                  = 'currency_code';
    const ZIPCODE                   = 'zipcode';
    const TRANS_MODE                = 'trans_mode';
    const BANK                      = 'bank';
    const MERCHANT_DB_NAME          = 'merchant_db_name';
    const INTERNATIONAL             = 'internationalcard';
    const TERMINAL_ACTIVE           = 'terminal_active';
    const LOCATION                  = 'location';
    const STATE                     = 'state';

    //Response Fields
    const GATEWAY_MID       = 'MID';
    const GATEWAY_TID       = 'TID';
    const RESPONSE_CODE     = 'Response Code';
    const RESPONSE_DESC     = 'Response description';

    // Onboard Response Status
    const SUCCESS       = '00';
    const DUPLICATE     = '01';
    const FAILURE       = '05';
    const FORMAT_ERROR  = '30';

    //Terminal creation fields
    const TERMINAL_CREATION_ENABLED         = 'enabled';
    const TERMINAL_CREATION_GATEWAY         = 'gateway';
    const TERMINAL_CREATION_GATEWAY_MID     = 'gateway_merchant_id';
    const TERMINAL_CREATION_GATEWAY_TID     = 'gateway_terminal_id';
    const TERMINAL_CREATION_ACQUIRER        = 'gateway_acquirer';
    const TERMINAL_CREATION_CATEGORY        = 'category';
    const TERMINAL_CREATION_CARD            = 'card';
    const TERMINAL_CREATION_RECURRING       = 'recurring';
    const TERMINAL_CREATION_INTERNATIONAL   = 'international';
    const TERMINAL_CREATION_MODE            = 'mode';
    const TERMINAL_CREATION_CURRENCY        = 'currency';
}
