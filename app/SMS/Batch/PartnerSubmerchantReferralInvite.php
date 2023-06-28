<?php

namespace RZP\SMS\Batch;

class PartnerSubmerchantReferralInvite extends Base
{
    protected $templateNamespace = "partnerships-experience";

    protected $sender = "RZRPAY";

    protected $template = "sms.partnerships.batch_complete_referral_invite";
}
