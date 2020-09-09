<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class PartnerSubmerchantInvite extends Base
{
    protected static $mailTag     = MailTags::BATCH_PARTNER_SUBMERCHANT_INVITE_FILE;

    protected static $sender      = Constants::PARTNER_SUBMERCHANT_INVITE;

    protected static $subjectLine = "Razorpay | Congratulations, Bulk Submerchant Onboarding Complete!";

    protected static $body        = 'Congratulations! Your file has been processed. We have attached the response file with this mail where you can check the status against each account.';
}
