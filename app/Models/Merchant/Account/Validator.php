<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
        Entity::NAME                        => 'required|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    ];
}
