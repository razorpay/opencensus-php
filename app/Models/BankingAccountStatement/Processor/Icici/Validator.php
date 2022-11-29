<?php

namespace RZP\Models\BankingAccountStatement\Processor\Icici;

use RZP\Base;
use RZP\Models\BankingAccount\Gateway\Icici;
use RZP\Models\BankingAccountStatement\Processor\Icici\RequestResponseFields as F;

class Validator extends Base\Validator
{
    protected static $iciciResponseRules = [
        F::ACCOUNT_NO        => 'required|string',
        F::AGGR_ID_RESPONSE  => 'required|string',
        F::CORP_ID_RESPONSE  => 'required|string',
        F::LASTTRID_RESPONSE => 'sometimes|string',
        F::RESPONSE          => 'required|string|in:SUCCESS',
        F::RECORD            => 'required|array',
        F::URN_RESPONSE      => 'required|string',
        F::USER_ID_RESPONSE  => 'required|string',

        F::RECORD . '.*.' . F::AMOUNT           => 'required',
        F::RECORD . '.*.' . F::BALANCE          => 'required',
        F::RECORD . '.*.' . F::CHEQUENO         => 'present',
        F::RECORD . '.*.' . F::REMARKS          => 'required',
        F::RECORD . '.*.' . F::TRANSACTION_ID   => 'required',
        F::RECORD . '.*.' . F::TRANSACTION_DATE => 'required|date_format:' . Gateway::DATE_TIME_FORMAT,
        F::RECORD . '.*.' . F::TYPE             => 'required|in:DR,CR',
        F::RECORD . '.*.' . F::VALUEDATE        => 'required|date_format:' . Gateway::DATE_FORMAT,

    ];

    protected static $iciciCredentialsRules = [
        Icici\Fields::CORP_USER     => 'required',
        Icici\Fields::CORP_ID       => 'required',
        Icici\Fields::URN           => 'required',
        Icici\Fields::CREDENTIALS   => 'present|nullable',
    ];
}
