<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $debitWalletRules = array(
        'MID'        => 'required|string',
        'SU'         => 'required|string',
        'FU'         => 'required|string',
        'TAX_REF_NO' => 'required|string',
        'AMT'        => 'required|numeric',
        'DATE'       => 'required|numeric',
        'HASH'       => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $refundRules = array(
        'MID'     => 'required|string',
        'AMT'     => 'required|numeric',
        'TAX_ID'  => 'required|string',
        'DATE'    => 'required|numeric',
        'REMARKS' => 'required|string'
    );

    protected static $verifyRules = array(
        'MID'        => 'required|string',
        'TAX_REF_NO' => 'required|string',
        'DATE'       => 'required|numeric',
    );
}
