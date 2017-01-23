<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
        Entity::NAME                        => 'required|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    ];

    protected static $uploadRules = [
        FileType::BUSINESS_PROOF            => 'required|file',
        FileType::BUSINESS_PAN              => 'required|file',
        FileType::BUSINESS_OPERATION_PROOF  => 'required|file',
        FileType::ADDRESS_PROOF             => 'required|file',
        FileType::PROMOTER_PROOF            => 'required|file',
        FileType::PROMOTER_PAN              => 'required|file',
        FileType::PROMOTER_ADDRESS          => 'required|file',
    ];
}
