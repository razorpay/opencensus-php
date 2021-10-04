<?php

namespace RZP\Mail\PayoutLink;

use App;
use Carbon\Carbon;
use RZP\Mail\Base\Mailable;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Logo as MerchantLogo;
use RZP\Models\Merchant\Entity as MerchantEntity;


class SendReminderInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.expiry_reminder';

    const SUBJECT = '%s %s - EXPIRING SOON';

    const EXPIRE_BY_DATE_FORMAT = 'd-M-Y';

    const EXPIRE_BY_TIME_FORMAT = 'H:i:s';

    protected $payoutLinkInfo;

    protected $merchantId;

    protected $toEmail;

    protected $merchant = null;

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

        $expireByDate = Carbon::createFromTimestamp($payoutLinkInfo['expire_by'], Timezone::IST)
            ->format(self::EXPIRE_BY_DATE_FORMAT);

        $expireByTime = Carbon::createFromTimestamp($payoutLinkInfo['expire_by'], Timezone::IST)
            ->format(self::EXPIRE_BY_TIME_FORMAT);

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
            'expire_by_date'        => $expireByDate,
            'expire_by_time'        => $expireByTime,
        ];

        $this->with($data);

        return $this;
    }
}
