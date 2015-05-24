<?php

namespace Models\Merchant;

use EE\Exception;
use Models\Base;
use Models\Payment\Processor\NetBanking;
use Illuminate\Support\MessageBag;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'id'                => 'required|alpha_num|size:14',
        'name'              => 'required|alpha_space_num|max:200',
        'email'             => 'required|email|unique:merchants',
    );

    protected static $editRules = array(
        'website'           => 'sometimes|url|max:255',
        'category'          => 'sometimes|numeric|digits:4',
        'international'     => 'sometimes|boolean',
    );
}
