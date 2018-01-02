<?php

namespace RZP\Models\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
         Entity::NAME         => 'required|alpha_space_num|max:200',
         Entity::EMAIL        => 'required|email',
         Entity::TNC_ACCEPTED => 'required|boolean|in:1',
         Entity::NOTES        => 'sometimes|array|max:15'
     ];
}
