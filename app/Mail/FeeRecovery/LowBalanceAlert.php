<?php

namespace RZP\Mail\FeeRecovery;

use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant;

class LowBalanceAlert extends Mailable
{
    const SUBJECT = 'Urgent: Low balance in your RazorpayX account could lead to Service Disruption';

    protected array $merchantData;

    /**
     * @throws \Exception
     */
    public function __construct(array $merchantData)
    {
        parent::__construct();

        $this->merchantData = $merchantData;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
            Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = [
            $this->merchantData[Merchant\Entity::EMAIL]
        ];

        $this->to($emails);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
            Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.fee_recovery.low_balance_alert');

        return $this;
    }

    protected function addMailData()
    {
        $data = $this->merchantData;

        $this->with($data);

        return $this;
    }
}
