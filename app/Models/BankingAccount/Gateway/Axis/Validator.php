<?php

namespace RZP\Models\BankingAccount\Gateway\Axis;

use RZP\Base;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;

class Validator extends Base\Validator
{
    protected static $axisCredentialsRules = [
        BaseFields::ID                                         => 'required|string',
        BaseFields::CORP_ID                                    => 'present|nullable|string',
        BaseFields::USER_ID                                    => 'present|nullable|string',
        BaseFields::URN                                        => 'present|nullable|string',
        BaseFields::CREDENTIALS                                => 'required|array',
        BaseFields::CREDENTIALS . '.' . Fields::ENCRYPTION_KEY => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::ENCRYPTION_IV  => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CLIENT_ID      => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CLIENT_SECRET  => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::CORP_CODE      => 'required|string',
    ];
}
