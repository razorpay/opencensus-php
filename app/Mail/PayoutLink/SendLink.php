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


class SendLink extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.send_link';

    const SUBJECT = 'Requesting details for %s %s (via Razorpay)';

    protected $payoutLinkId;

    protected $payoutLink = null;

    public function __construct(string $payoutLinkId)
    {
        parent::__construct();

        $this->payoutLinkId = $payoutLinkId;
    }

    protected function addRecipients()
    {
        $payoutLink = $this->getPayoutLink();

        $this->to($payoutLink->getContactEmail());

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT]);

        return $this;
    }

    protected function getPayoutLink() : PayoutLinkEntity
    {
        if ($this->payoutLink === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->payoutLink = $repo->payout_link->findByPublicId($this->payoutLinkId);
        }

        return $this->payoutLink;
    }

    protected function getMerchant(): MerchantEntity
    {
        return $this->getPayoutLink()->merchant;
    }

    protected function addSender()
    {
        return $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                           Constants::HEADERS[Constants::NOREPLY]);
    }

    protected function addHtmlView()
    {
        $this->view(self::EMAIL_TEMPLATE);

        return $this;
    }

    protected function addSubject()
    {
        $payoutLink = $this->getPayoutLink();

        $subject = sprintf(self::SUBJECT,
                           $payoutLink->merchant->getBillingLabel(),
                           $payoutLink->getPurpose()
        );

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $payoutLink = $this->getPayoutLink();

        $merchant = $payoutLink->merchant;

        $displayName = $merchant->getBillingLabel();

        $data = [
            'billing_label'         => $displayName,
            'purpose'               => $payoutLink->getPurpose(),
            'brand_logo'            => $merchant->getFullLogoUrlWithSize(MerchantLogo::MEDIUM_SIZE),
            'brand_color'           => $merchant->getBrandColorElseDefault(),
            'short_url'             => $payoutLink->getShortUrl(),
            'amount'                => $payoutLink->getFormattedAmount(),
            'contrast_color'        => $merchant->getContrastOfBrandColor(),
            'description'           => $payoutLink->getDescription(),
            'contact_name'          => $payoutLink->getContactName(),
            'contact_email'         => $payoutLink->getContactEmail(),
            'contact_phone'         => $payoutLink->getContactPhoneNumber(),
        ];

        $this->with($data);

        return $this;
    }
}
