<?php

namespace RZP\Models\BankingAccount;

class RblFields
{
    const SUBCORP_ID            = 'subcorp_id';
    const SUBCORP_USER_ID       = 'subcorp_user_id';
    const SUBCORP_USER_PASSWORD = 'subcorp_user_password';
    const CLIENT_ID             = 'client_id';
    const CLIENT_SECRET         = 'client_secret';
    const USERNAME              = 'username';
    const PASSWORD              = 'password';
    const MOZART_IDENTIFIER     = 'mozart_identifier';

    public static $rblFieldsToEntityMap = [
        self::SUBCORP_ID            => Entity::USER1,
        self::SUBCORP_USER_ID       => Entity::SECRET1,
        self::SUBCORP_USER_PASSWORD => Entity::SECRET2
    ];
}
