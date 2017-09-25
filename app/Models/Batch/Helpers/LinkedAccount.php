<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\Batch\Header;

class LinkedAccount
{
    public static function getCreateAccountInput(array $entry): array
    {
        return [
            Merchant\Entity::NAME => $entry[Header::BUSINESS_NAME]
        ];
    }

    public static function getAccountDetailInput(array $entry): array
    {
        return [

        ];
    }
}
