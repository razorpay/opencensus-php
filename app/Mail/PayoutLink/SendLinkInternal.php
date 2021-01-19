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


class SendLinkInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.send_link';

    const SUBJECT = 'Requesting details for %s %s (via Razorpay)';

    protected $payoutLinkInfo;

    protected $merchantId;

    protected $toEmail;

    protected $merchant = null;

    // hardcoding this here for now. will send from MicroService in phase 2.
    protected $merchantVsBccEmails = [
        // ixigo merchant
        '8RerE9oY0d7rbC' => 'communication@travenues.com',
        // test merchant
        '10000000000000' => 'test@rzp.com',
        // prod test merchant
        'DESdesq9lfHWil' => 'aravinthan.subramaniam@razorpay.com'
    ];

    public function __construct(array $payoutLinkInfo, string $merchantId, string $toEmail)
    {
        parent::__construct();

        $this->payoutLinkInfo = $payoutLinkInfo;

        $this->merchantId = $merchantId;

        $this->toEmail = $toEmail;
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

        return $this;
    }

    protected function addBcc()
    {
        $merchant = $this->getMerchant();

        $merchantId = $merchant->getId();

        if (array_key_exists($merchantId, $this->merchantVsBccEmails) === true)
        {
            $this->bcc($this->merchantVsBccEmails[$merchantId]);
        }

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY]);

        return $this;
    }

    protected function getPayoutLinkInfo() : array
    {
        return $this->payoutLinkInfo;
    }

    protected function getMerchant(): MerchantEntity
    {
        if ($this->merchant === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->merchant = $repo->merchant->findByPublicId($this->merchantId);
        }

        return $this->merchant;
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
        $payoutLinkInfo = $this->getPayoutLinkInfo();

        $subject = sprintf(self::SUBJECT,
                            $this->getMerchant()->getBillingLabel(),
                            $payoutLinkInfo['purpose']
        );

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $payoutLinkInfo = $this->getPayoutLinkInfo();

        $merchant = $this->getMerchant();

        $displayName = $merchant->getBillingLabel();

        $data = [
            'billing_label'         => $displayName,
            'purpose'               => $payoutLinkInfo['purpose'],
            'brand_logo'            => $merchant->getFullLogoUrlWithSize(MerchantLogo::MEDIUM_SIZE),
            'brand_color'           => $merchant->getBrandColorElseDefault(),
            'short_url'             => $payoutLinkInfo['short_url'],
            'amount'                => $payoutLinkInfo['amount'],
            'contrast_color'        => $merchant->getContrastOfBrandColor(),
            'description'           => $payoutLinkInfo['description'],
            'contact_name'          => $payoutLinkInfo['contact_name'],
            'contact_email'         => $payoutLinkInfo['contact_email'],
            'contact_phone'         => $payoutLinkInfo['contact_phone_number'],
        ];

        $this->with($data);

        return $this;
    }
}
