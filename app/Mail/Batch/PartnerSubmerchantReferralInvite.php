<?php

namespace RZP\Mail\Batch;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class PartnerSubmerchantReferralInvite extends Base
{

    protected static $mailTag     = MailTags::BATCH_PARTNER_SUBMERCHANT_REFERRAL_INVITE_FILE;

    protected static $sender      = Constants::PARTNER_SUBMERCHANT_REFERRAL_INVITE;

    protected static $subjectLine = 'Status of invited Merchant Accounts';

    protected function addHtmlView()
    {
        $this->view('emails.mjml.merchant.partner.submerchant.referral_invite', ['merchantName' => $this->merchant['name']]);

        return $this;
    }
}
