<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Error\ErrorCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\BadRequestException;

class AccountActivationConfirmation extends Mailable
{
    const GUIDE_TO_GO_LIVE_URL  = 'https://razorpay.com/docs/razorpayx/api/';

    const SUPPORT_URL_VALUE     = 'https://x.razorpay.com/?support=ticket';

    const LEARN_MORE_URL        = 'https://razorpay.com/docs/razorpayx/';

    const SUBJECT               = 'Your RazorpayX account is now live';

    const TEMPLATE_PATH         = 'emails.merchant.razorpayx.account_activation_confirmation';

    const BENEFICIARY_NAME      = 'beneficiary_name';

    const ACCOUNT_NUMBER        = 'account_number';

    const ACCOUNT_IFSC          = 'account_ifsc';

    const DASHBOARD_URL         = 'dashboard_url';

    const LEARN_MORE            = 'learn_more_url';

    const GUIDE_TO_GO_LIVE      = 'guide_to_go_live_url';

    const SUPPORT_URL           = 'support_url';

    protected $bankingAccount;

    protected $merchantId;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;
    }

    protected function getBankingAccount()
    {
        $app = App::getFacadeRoot();

        $merchant = $app['repo']->merchant->find($this->merchantId);

        $bankingAccounts = $merchant->bankingAccounts()->get();

        // todo, need a better way to determine which banking account.
        // right now, this is part of onboarding flow, and only a VA banking account will be present.
        // hence index(0) will work. Going forward, in case there is a flow where
        // at the time of on-boarding the person has 2 active banking account,
        // then it will be tough to determine which banking account is being activated.
        return $bankingAccounts[0];
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {
        $bankingAccount = $this->getBankingAccount();

        $this->to($bankingAccount->getBeneficiaryEmail());

        return $this;
    }

    protected function addReplyTo()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addMailData()
    {
        $config = App::getFacadeRoot()['config'];

        $bankingAccount = $this->getBankingAccount();

        $data = [
            self::BENEFICIARY_NAME => $bankingAccount->getBeneficiaryName(),
            self::ACCOUNT_NUMBER   => $bankingAccount->getAccountNumber(),
            self::ACCOUNT_IFSC     => $bankingAccount->getAccountIfsc(),
            self::DASHBOARD_URL    => $config['applications.banking_service_url'],
            self::LEARN_MORE       => self::LEARN_MORE_URL,
            self::GUIDE_TO_GO_LIVE => self::GUIDE_TO_GO_LIVE_URL,
            self::SUPPORT_URL      => self::SUPPORT_URL_VALUE
        ];

        $this->with($data);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }
}

