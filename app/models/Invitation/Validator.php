<?php

namespace Models\Invitation;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $sendInvitationRules = array(
        'email' => 'required|max:255|email',
        'role' => 'required|in:owner,manager,operations,finance,developer',
    );
}