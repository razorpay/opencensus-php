<?php

namespace Models\Merchant;

use Models\Base;

class Merchant extends Base\Validator
{
    protected static $createRules = array(
        'id'    =>  'required|numeric'
    );

}
