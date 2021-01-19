<?php

namespace RZP\Mail\PayoutLink;

use App;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\PayoutLink\Entity;
use RZP\Models\Merchant\Logo as MerchantLogo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;
use RZP\Models\Vpa\Entity as VpaEntity;

class SuccessInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.success';

    const SUBJECT = '%s %s is Successful';

    const DATE_TIME_FORMAT = "d M 'y h:i A";

    protected $payoutLinkInfo;

    protected $settings;

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

    public function __construct(array $payoutLinkInfo, array $settings, string $merchantId, string $toEmail)
    {
        parent::__construct();

        $this->payoutLinkInfo = $payoutLinkInfo;

        $this->settings = $settings;

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

    protected function getPayoutInfo($payoutLinkInfo) : array
    {
        if (key_exists('payouts', $payoutLinkInfo)
            && key_exists('count', $payoutLinkInfo['payouts']))
        {
            $payoutsCount = $payoutLinkInfo['payouts']['count'];

            if ($payoutsCount > 0)
            {
                return $payoutLinkInfo['payouts']['items'][0];
            }
        }
        return [];
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

    protected function getSettings() : array
    {
        if (key_exists('mode', $this->settings))
        {
            return $this->settings['mode'];
        }
        return [];
    }

    protected function addMailData()
    {
        $payoutLinkInfo = $this->getPayoutLinkInfo();

        $payoutInfo = $this->getPayoutInfo($payoutLinkInfo);

        $merchant = $this->getMerchant();

        $displayName = $merchant->getBillingLabel();

        /** @var \RZP\Models\Vpa\Entity|\RZP\Models\BankAccount\Entity $account */
        /** @var \RZP\Models\PayoutLink\Entity $payoutLink */
        $repo = App::getFacadeRoot()['repo'];

        try
        {
            $fundAccount = $repo->fund_account->findByPublicIdAndMerchant($payoutLinkInfo['fund_account_id'] ?? '', $merchant);

            $account = optional($fundAccount)->account;
        }
        catch (\Exception $e)
        {
            $fundAccount = null;
        }

        $payoutProcessedAt = null;

        $payoutUtr = null;

        if(key_exists('processed_at', $payoutInfo))
        {
            $payoutProcessedAt = $payoutInfo['processed_at'];

            $payoutProcessedAt = ($payoutProcessedAt != null ?
                $this->format($payoutProcessedAt, self::DATE_TIME_FORMAT) :
                null);
        }

        if(key_exists('utr', $payoutInfo))
        {
            $payoutUtr = $payoutInfo['utr'];
        }

        $settings = $this->getSettings();

        $data = [
            'billing_label'         => $displayName,
            'purpose'               => $payoutLinkInfo['purpose'] ?? null,
            'brand_logo'            => $merchant->getFullLogoUrlWithSize(MerchantLogo::MEDIUM_SIZE),
            'brand_color'           => $merchant->getBrandColorElseDefault(),
            'short_url'             => $payoutLinkInfo['short_url'] ?? null,
            'amount'                => $payoutLinkInfo['amount'] ?? null,
            'contrast_color'        => $merchant->getContrastOfBrandColor(),
            'description'           => $payoutLinkInfo['description'] ?? null,
            'contact_name'          => $payoutLinkInfo['contact_name'] ?? null,
            'contact_email'         => $payoutLinkInfo['contact_email'] ?? null,
            'contact_phone'         => $payoutLinkInfo['contact_phone_number'] ?? null,
            'utr'                           => $payoutUtr,
            'payout_link_success_date'      => $payoutProcessedAt,
            'support_contact'               => $settings[Entity::SUPPORT_CONTACT] ?? null,
            'support_email'                 => $settings[Entity::SUPPORT_EMAIL] ?? null,
            'support_url'                   => $settings[Entity::SUPPORT_URL] ?? null
        ];

        if($fundAccount != null)
        {
            if ($fundAccount->getAccountType() === FundAccountEntity::BANK_ACCOUNT)
            {
                $data = array_merge($data,[
                    'fund_account_number'   => $account->getAccountNumber(),
                    'fund_account_name'     => $account->getBeneficiaryName(),
                    'fund_account_ifsc'     => $account->getIfscCode(),
                    'fund_account_bank_name'=> $account->getBankName(),
                ]);
            }
            else if ($fundAccount->getAccountType() === FundAccountEntity::VPA)
            {
                $data = array_merge($data,[
                    'fund_account_vpa'      => $account->getAddress(),
                    'fund_account_name'     => $account->getUsername(),
                ]);
            }
        }

        $this->with($data);

        return $this;
    }

    protected function format($timestamp, $format)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
            ->format($format);
    }
}
