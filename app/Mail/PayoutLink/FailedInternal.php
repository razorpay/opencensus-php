<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Models\Settings;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\PayoutLink\Entity;
use RZP\Models\Merchant\Logo as MerchantLogo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\Vpa\Entity as VpaEntity;
use RZP\Models\BankAccount\Entity as BankAccountEntity;

class FailedInternal extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.failed';

    const SUBJECT = '%s %s has failed - Please retry';

    protected $payoutLinkInfo;

    protected $settings;

    protected $merchantId;

    protected $toEmail;

    protected $merchant = null;

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

        $merchant = $this->getMerchant();

        $displayName = $merchant->getBillingLabel();

        /** @var \RZP\Models\Vpa\Entity|\RZP\Models\BankAccount\Entity $account */
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
            'support_contact'       => $settings[Entity::SUPPORT_CONTACT] ?? null,
            'support_email'         => $settings[Entity::SUPPORT_EMAIL] ?? null,
            'support_url'           => $settings[Entity::SUPPORT_URL] ?? null
        ];

        if($fundAccount != null)
        {
            if ($fundAccount->getAccountType() === FundAccountEntity::BANK_ACCOUNT)
            {
                $data = array_merge($data,[
                    'fund_account_name'     => $account->getBeneficiaryName(),
                    'fund_account_number'   => $account->getAccountNumber(),
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
}
