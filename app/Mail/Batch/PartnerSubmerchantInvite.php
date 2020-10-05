<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class PartnerSubmerchantInvite extends Base
{
    protected static $mailTag     = MailTags::BATCH_PARTNER_SUBMERCHANT_INVITE_FILE;

    protected static $sender      = Constants::PARTNER_SUBMERCHANT_INVITE;

    protected static $subjectLine = 'Status of added Merchant Accounts';

    protected function addHtmlView()
    {
        $this->view('emails.mjml.merchant.partner.submerchant.invite', ['merchantName' => $this->merchant['name']]);

        return $this;
    }
}
