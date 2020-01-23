<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class L2SubmissionWhitelist extends Mailable
{
    const SUPPORT_URL    = 'https://x.razorpay.com/?support=ticket';

    const LEARN_MORE_URL = 'https://razorpay.com/docs/razorpayx/';

    const SUBJECT        = 'KYC Form submitted for Razorpay';

    const TEMPLATE_PATH  = 'emails.merchant.razorpayx.l2_submission_whitelisted';

    protected $bankingAccount;

    protected $merchant;

    protected $merchantId;

    protected $config;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;
    }

    protected function getMerchant()
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        if ($this->merchant === null)
        {
            $this->merchant = $repo->merchant->find($this->merchantId);
        }

        return $this->merchant;
    }

    protected function getBankingAccount()
    {
        if ($this->bankingAccount === null)
        {
            $merchant = $this->getMerchant();

            // todo, need a better way to determine which banking account.
            // right now, this is part of onboarding flow, and only a VA banking account will be present.
            // hence index(0) will work. Going forward, in case there is a flow where
            // at the time of on-boarding the person has 2 active banking account,
            // then it will be tough to determine which banking account is being activated.
            $this->bankingAccount = $merchant->bankingAccounts->first();
        }

        return $this->bankingAccount;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $bankingAccount = $this->getBankingAccount();

        $data = [
            'learn_more_url'                       => self::LEARN_MORE_URL,
            'support_url'                          => self::SUPPORT_URL,
            'view_dashboard_url'                   => $config['applications.banking_service_url'],
            BankingAccountEntity::ACCOUNT_IFSC     => $bankingAccount->getAccountIfsc(),
            BankingAccountEntity::ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
            BankingAccountEntity::BENEFICIARY_NAME => $bankingAccount->getBeneficiaryName()
        ];

        $this->with($data);

        return $this;
    }

    protected function addRecipients()
    {
        $bankingAccount = $this->getBankingAccount();

        $merchant = $this->getMerchant();

        $this->to($merchant->getEmail(),
                  $bankingAccount->getBeneficiaryName());

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }
}
