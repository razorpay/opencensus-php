<?php

namespace RZP\Mail\Batch;

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

    // Adding OPS team emails for tracking the submerchant invite file.
    protected function addBcc()
    {
        $emails = Constants::MAIL_ADDRESSES[Constants::PARTNER_SUBMERCHANT_INVITE_INTERNAL];

        $this->bcc($emails);

        return $this;
    }
}
