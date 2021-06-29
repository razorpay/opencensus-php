<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\BankAccount\Entity;
use function Clue\StreamFilter\append;
use RZP\Models\Merchant\Logo as MerchantLogo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;


class SendDemoLinkInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.send_link';

    const SUBJECT = 'Requesting details for %s %s (via Razorpay)';

    protected $payoutLinkInfo;

    protected $merchantInfo;


    protected $toEmail;

    protected $merchant = null;


    public function __construct(array $payoutLinkInfo, array $merchantInfo, string $toEmail)
    {
        parent::__construct();

        $this->payoutLinkInfo = $payoutLinkInfo;

        $this->merchantInfo = $merchantInfo;

        $this->toEmail = $toEmail;
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

    protected function getPayoutLinkInfo(): array
    {
        return $this->payoutLinkInfo;
    }

    protected function getMerchantInfo(): array
    {
        return $this->merchantInfo;
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
        $subject = 'Requesting details for Razorpay Payout Link (via Razorpay)';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $payoutLinkInfo = $this->getPayoutLinkInfo();

        $merchantInfo = $this->getMerchantInfo();

        $data = [
            'billing_label' => $merchantInfo['billing_label'],
            'purpose' => $payoutLinkInfo['purpose'],
            'brand_logo' => $merchantInfo['brand_logo'],
            'brand_color' => $merchantInfo['brand_color'],
            'short_url' => $payoutLinkInfo['short_url'],
            'amount' => $payoutLinkInfo['amount'],
            'contrast_color' => $merchantInfo['brand_color_contrast'],
            'description' => $payoutLinkInfo['description'],
            'contact_name' => $payoutLinkInfo['contact_name'],
            'contact_email' => $payoutLinkInfo['contact_email'],
            'contact_phone' => $payoutLinkInfo['contact_phone_number'],
        ];

        $this->with($data);

        return $this;
    }
}
