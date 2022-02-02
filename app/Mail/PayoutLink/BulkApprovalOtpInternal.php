<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class BulkApprovalOtpInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.bulk_approve_otp';

    const SUBJECT = 'One Time Password (OTP) for Payout Links Approval';

    protected $otp;

    protected $toEmail;

    protected $validity;

    protected $payoutLinksCount;

    protected $totalAmount;

    protected $merchant = null;

    public function __construct(int $linksCount, float $totalAmount, string $toEmail, string $otp, string $validity)
    {
        parent::__construct();

        $this->payoutLinksCount = $linksCount;

        $this->totalAmount = $totalAmount;

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
            'otp'                   => $this->otp,
            'payout_links_count'    => $this->payoutLinksCount,
            'total_amount'          => $this->totalAmount,
            'validity'              => $this->validity,
        ];

        $this->with($data);

        return $this;
    }
}
