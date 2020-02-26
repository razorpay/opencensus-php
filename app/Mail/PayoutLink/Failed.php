<?php

namespace RZP\Mail\PayoutLink;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Logo as MerchantLogo;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\PayoutLink\Entity as PayoutLinkEntity;
use RZP\Models\FundAccount\Entity as FundAccountEntity;

class Failed extends Mailable
{
    const EMAIL_TEMPLATE = 'emails.payout_link.failed';

    const SUBJECT = '%s %s has failed - Please retry';


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
                           $payoutLink->merchant->getDisplayNameElseName(),
                           $payoutLink->getPurpose()
        );

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $payoutLink = $this->getPayoutLink();

        $merchant = $payoutLink->merchant;

        $displayName = $merchant->getDisplayNameElseName();

        $account = $payoutLink->fundAccount->account;

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
            'contact_phone'         => $payoutLink->getContactPhoneNumber()
        ];

        if ($payoutLink->fundAccount->getAccountType() === FundAccountEntity::BANK_ACCOUNT)
        {
            array_push($data,[
                'fund_account_number'   => $account->getAccountNumber(),
                'fund_account_name'     => $account->getBeneficiaryName(),
                'fund_account_ifsc'     => $account->getIfscCode(),
            ]);
        }
        else if ($payoutLink->fundAccount->getAccountType() === FundAccountEntity::VPA)
        {
            array_push($data,[
                'fund_account_vpa'      => $account->getHandle(),
                'fund_account_name'     => $account->getUsername(),
            ]);
        }

        $this->with($data);

        return $this;
    }
}
