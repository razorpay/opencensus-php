<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Error\ErrorCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\BadRequestException;

class AccountActivationConfirmation extends Mailable
{
    const GUIDE_TO_GO_LIVE_URL = '';

    const SUPPORT_URL          = '';

    const LEARN_MORE_URL       = '';

    const SUBJECT              = 'Your RazorpayX account is now live';

    const TEMPLATE_PATH        = 'emails.merchant.razorpayx.account_activation_confirmation';

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
            'beneficiary_name'     => $bankingAccount->getBeneficiaryName(),
            'account_number'       => $bankingAccount->getAccountNumber(),
            'account_ifsc'         => $bankingAccount->getAccountIfsc(),
            'dashboard_url'        => $config['applications.banking_service_url'],
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

