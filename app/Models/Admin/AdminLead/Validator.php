<?php

namespace RZP\Models\Admin\AdminLead;

use RZP\Models\Admin\Base;

class Validator extends Base\Validator
{
    public static $createRules = [
        Entity::TOKEN     => 'required|string|max:40',
        Entity::EMAIL     => 'required|email',
        Entity::FORM_DATA => 'required|array',
    ];

    protected static $editRules = [
        Entity::SIGNED_UP_AT => 'sometimes|integer',
    ];

    protected static $sendInvitationRules = [
        'contact_email'         => 'required|max:255|email',
        'channel_code'          => 'required',
        'channel_code_others'   => 'sometimes',
        'crm_next_no'           => 'required',
        'db_token_no'           => 'required',
        'branch_lts_no'         => 'required',
        'branch_code'           => 'required',
        'source_code'           => 'required',
        'promo_code'            => 'required',
        'lg_code'               => 'required',
        'lc_ro_code'            => 'required',
        'mrm_code'              => 'required',
        'mcc_category'          => 'required',
        'mcc_code'              => 'required',
        'merchant_type'         => 'required',
        'merchant_name'         => 'required',
        'contact_name'          => 'required',
        'dba_name'              => 'required',
        'country_code'          => 'required|in:IN,MY'
    ];

    public $isOrgSpecificValidationSupported = false;
}
