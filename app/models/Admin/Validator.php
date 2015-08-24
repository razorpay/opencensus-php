<?php

namespace Models\Admin;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $sendTestNewsletterRules = [
        'msg'     => 'required|max:10000',
        'subj_1'  => 'required|alpha_space_num|max:200',
        'subj_2'  => 'sometimes|alpha_space_num|max:200',
        'email'   => 'required|email'
    ];
}
