<?php

namespace App\Invitation;

use App\Base;

class Validator extends Base\Validator
{
    /**
     * The validation rules for sending an invitation.
     * @var array $roles
     */
    protected static $sendInvitationRules = array(
        'email' => 'required|max:255|email',
        'role'  => 'required|in:manager,operations,finance',
    );

    /**
     * The validation rules for updating an invitation.
     * @var array $roles
     */
    protected static $updateInvitationRules = array(
        'role' => 'required|in:manager',
    );
}
