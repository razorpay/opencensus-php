<?php

namespace RZP\Mail\User;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class BulkPayoutSummary extends Mailable
{
    const SUBJECT            = "Bulk Payouts Status : %s";

    const BULK_PAYOUT_SUMMARY_EMAIL_TEMPLATE_PATH      = 'emails.user.bulk_summary';

    protected $data;

    protected $user;

    public function __construct($mailData, $user)
    {
        parent::__construct();

        $this->data = $mailData;

        $this->user = $user;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
            Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $replyTo = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($replyTo);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->user->getEmail());

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(sprintf(self::SUBJECT, $this->data['batch_name']));

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::BULK_PAYOUT_SUMMARY_EMAIL_TEMPLATE_PATH);
        return $this;
    }
}
