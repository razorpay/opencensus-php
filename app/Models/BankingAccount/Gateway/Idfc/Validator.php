<?php

namespace RZP\Models\BankingAccount\Gateway\Idfc;

use RZP\Base;
use RZP\Models\BankingAccount\Gateway\Fields as BaseFields;

class Validator extends Base\Validator
{
    protected static $idfcCredentialsRules = [
        BaseFields::ID                                            => 'required|string',
        BaseFields::CORP_ID                                       => 'present|nullable|string',
        BaseFields::USER_ID                                       => 'present|nullable|string',
        BaseFields::URN                                           => 'present|nullable|string',
        BaseFields::CREDENTIALS                                   => 'required|array',
        BaseFields::CREDENTIALS . '.' . Fields::hexEncryptionKey  => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::transferClientId  => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::transferKid       => 'required|string',
        BaseFields::CREDENTIALS . '.' . Fields::transferSource    => 'required|string',
    ];
}
