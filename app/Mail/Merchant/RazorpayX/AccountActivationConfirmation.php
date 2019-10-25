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

    protected $repo;

    protected $config;

    protected $merchant;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $this->config = $app['config'];

        $this->merchant = $app['repo']->merchant
                                      ->find($merchantId);
    }

    protected function getBankingAccount()
    {
        /**
         * Assumption is since this is an Instant Activation Email,
         * the merchant will have only one Banking Account, which will be the Virtual Account
         * Hence, we will get the first banking account
         */
        $bankingAccounts = $this->merchant->bankingAccounts()->get();

        if ($bankingAccounts->count() === 0)
        {
            throw new BadRequestException(ErrorCode::FINAL_VA_ACCOUNT_CONFIRM_EMAIL_FAILED,
                                          null,
                                          [
                                              'merchant_id' => $this->merchantId
                                          ],
                                          'No Banking Account found for the merchant: ' . $this->merchantId);
        }

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
        $bankingAccount = $this->getBankingAccount();

        $data = [
            'beneficiary_name'     => $bankingAccount->getBeneficiaryName(),
            'account_number'       => $bankingAccount->getAccountNumber(),
            'account_ifsc'         => $bankingAccount->getAccountIfsc(),
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

