<?php

namespace Models\Merchant;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
    	'id'    =>  'required|alpha_num|size:14',
        'name'  =>	'required|alpha_space|max:200',
        'email' =>	'required|email|unique:merchants'
    );
}
