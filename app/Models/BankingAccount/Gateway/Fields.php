<?php

namespace RZP\Models\BankingAccount\Gateway;

class Fields
{
    const SOURCE_ACCOUNT        = 'source_account';
    const SOURCE_ACCOUNT_NUMBER = 'account_number';

    // Root response fields in BAS credential fetch response
    const ID          = 'id';
    const CORP_ID     = 'corp_id';
    const USER_ID     = 'user_id';
    const URN         = 'urn';
    const CREDENTIALS = 'credentials';
}
