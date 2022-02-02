<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class ApprovalOtpInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.approve_otp';

    const SUBJECT = 'One Time Password (OTP) for Payout Link Approval';

    protected $otp;

    protected $toEmail;

    protected $validity;

    protected $payoutLinkInfo;

    protected $merchant = null;

    public function __construct(array $payoutLinkInfo, string $toEmail, string $otp, string $validity)
    {
        parent::__construct();

        $this->payoutLinkInfo = $payoutLinkInfo;

        $this->toEmail = $toEmail;

        $this->otp = $otp;

        $this->validity = $validity;
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

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
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'otp'               => $this->otp,
            'payout_link_id'    => $this->payoutLinkInfo['id'],
            'amount'            => $this->payoutLinkInfo['amount'],
            'account_number'    => mask_except_last4($this->payoutLinkInfo['account_number']),
            'validity'          => $this->validity,
        ];

        $this->with($data);

        return $this;
    }
}
