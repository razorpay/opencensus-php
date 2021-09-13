<?php

namespace RZP\Mail\Payout;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class PendingApprovals extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout.pending_approvals';

    const SUBJECT = 'Payouts Awaiting Approval for %s.';

    protected $input;

    public function __construct(array $input)
    {
        parent::__construct();

        $this->input = $input;
    }

    protected function addRecipients()
    {
        $this->to($this->input['email']);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                           Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(sprintf(self::SUBJECT, $this->input['business_name']));

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->input);

        return $this;
    }
}
