<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Validator;

class PartnerSubmerchantReferralInvite extends Base
{
    public function addSettingsIfRequired(& $input)
    {
        $config = $input['config'] ?? [];
        if (empty($config) == false)
        {
            (new Validator())->validateInput('partner_submerchant_referral_invite_config', $config);
        }
    }
}
