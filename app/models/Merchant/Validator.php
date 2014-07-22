<?php

namespace Models\Merchant;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'id'    =>  'required|alpha_num|max:24'
    );
}
