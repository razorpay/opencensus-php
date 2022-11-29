<?php

namespace RZP\Models\BankingAccount\Gateway\Icici;

class Fields
{
    // Fields for Fetch Balance API
    const CONTAINS              = 'contains';
    const ENTITIES              = 'entities';
    const SOURCE_ACCOUNT        = 'source_account';
    const SOURCE_ACCOUNT_NUMBER = 'account_number';
    const CREDENTIALS           = 'credentials';
    const CORP_ID               = 'CrpId';
    const CORP_USER             = 'CrpUsr';
    const AGGR_ID               = 'AGGR_ID';
    const AGGR_NAME             = 'AGGR_NAME';
    const URN                   = 'URN';
    const BENEFICIARY_API_KEY   = 'beneficiaryApikey';
    const MERCHANT_ID           = 'merchant_id';

    // Response for balance fetch api
    const DATA    = 'data';
    const BALANCE = 'balance';

    // Fields for Balance Fetch in DB (details table)
    const USER_ID = 'user_id';
    const URN_DB  = 'urn';

    // fields fetched from config
    const AGGR_ID_CONFIG             = 'aggr_id';
    const AGGR_NAME_CONFIG           = 'aggr_name';
    const BENEFICIARY_API_KEY_CONFIG = 'beneficiary_api_key';
}
