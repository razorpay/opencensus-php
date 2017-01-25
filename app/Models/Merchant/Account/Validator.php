<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
        Entity::NAME                        => 'required|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    ];

    protected static $filesRules = [
        FileType::BUSINESS_PROOF            => 'sometimes|file',
        FileType::BUSINESS_PAN              => 'sometimes|file',
        FileType::BUSINESS_OPERATION_PROOF  => 'sometimes|file',
        FileType::ADDRESS_PROOF             => 'sometimes|file',
        FileType::PROMOTER_PROOF            => 'sometimes|file',
        FileType::PROMOTER_PAN              => 'sometimes|file',
        FileType::PROMOTER_ADDRESS          => 'sometimes|file',
    ];
}
