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
        self::SUBCORP_ID            => Entity::REFERENCE1,
        self::SUBCORP_USER_ID       => Entity::USERNAME,
        self::SUBCORP_USER_PASSWORD => Entity::PASSWORD
    ];

    public static function getRblAttributesToSave(array $input)
    {
        $map = self::$rblFieldsToEntityMap;

        $attr = [];

        foreach ($input as $key => $value)
        {
            if (isset($map[$key]))
            {
                $newKey        = $map[$key];
                $attr[$newKey] = $value;
            }
        }

        return $attr;
    }
}
