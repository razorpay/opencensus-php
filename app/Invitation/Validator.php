<?php

namespace App\Invitation;

use App\Base;

class Validator extends Base\Validator
{
    const VALID_ROLE_REQUIRED = 'required|in:manager,operations,finance,support,admin,sellerapp';
    /**
     * The validation rules for sending an invitation.
     * @var array $roles
     */
    protected static $sendInvitationRules = array(
        'email' => 'required|max:255|email',
        'role'  => self::VALID_ROLE_REQUIRED,
    );

    /**
     * The validation rules for updating an invitation.
     * @var array $roles
     */
    protected static $updateInvitationRules = array(
        'role' => self::VALID_ROLE_REQUIRED,
    );
}
