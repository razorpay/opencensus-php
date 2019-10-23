<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;

class AccountActivationConfirmation extends Mailable
{
    const GUIDE_TO_GO_LIVE_URL = '';

    const SUPPORT_URL          = '';

    const LEARN_MORE_URL       = '';

    const SUBJECT              = 'Your RazorpayX account is now live';

    const TEMPLATE_PATH        = 'emails.merchant.razorpayx.account_activation_confirmation';

    protected $bankingAccount;

    protected $config;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->config = $app['config'];

        $repo = $app['repo'];

        $merchant = $repo->merchant->find($merchantId);

        /***
         * Assumption is since this is an Instant Activation Email,
         * the merchant will have only one Banking Account, which will be the Virtual Account
         * Hence, we will get the first banking account
         */
        $bankingAccounts = $merchant->bankingAccounts()->get();

        if (empty($bankingAccounts) === false)
        {
            $this->bankingAccount = $bankingAccounts[0];
        }
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::X_SUPPORT],
                    Constants::HEADERS[Constants::X_SUPPORT]);

        return $this;
    }

    protected function addRecipients()
    {
        if ( empty($this->bankingAccount) === false )
        {
            $this->to($this->bankingAccount->getBeneficiaryEmail());
        }

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
        $data = [
            'beneficiary_name'     => $this->bankingAccount->getBeneficiaryName(),
            'account_number'       => $this->bankingAccount->getAccountNumber(),
            'account_ifsc'         => $this->bankingAccount->getAccountIfsc(),
            'dashboard_url'        => $this->config['applications.banking_service_url'],
            'learn_more_url'       => self::LEARN_MORE_URL,
            'guide_to_go_live_url' => self::GUIDE_TO_GO_LIVE_URL,
            'support_url'          => self::SUPPORT_URL
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

