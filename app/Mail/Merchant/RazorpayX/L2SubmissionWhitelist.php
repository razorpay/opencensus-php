<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Error\ErrorCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class L2SubmissionWhitelist extends Mailable
{
    const SUPPORT_URL    = '';

    const LEARN_MORE_URL = '';

    const SUBJECT        = 'KYC Form submitted for Razorpay';

    const TEMPLATE_PATH  = 'emails.merchant.razorpayx.l2_submission_whitelisted';

    protected $bankingAccount;

    protected $merchantId;

    protected $config;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;

        $app = App::getFacadeRoot();

    }

    protected function getBankingAccount()
    {
        if ($this->bankingAccount === null)
        {
            $app = App::getFacadeRoot();

            $repo = $app['repo'];

            $merchant = $repo->merchant->find($this->merchantId);

            /***
             * Assumption is since this is an Instant Activation Email,
             * the merchant will have only one Banking Account, which will be the Virtual Account
             * Hence, we will get the first banking account
             */
            $bankingAccounts = $merchant->bankingAccounts()->get();

            if ($bankingAccounts->count() !== 0)
            {
                $this->bankingAccount = $bankingAccounts[0];
            }
            else
            {
                // this will be an exception and no mail should go. Because we do not have enough data
                throw new BadRequestException(ErrorCode::BAD_REQUEST_L2_SUBMISSION_WHITELIST_EMAIL_FAILED,
                                              null,
                                              [
                                                  'merchant_id' => $this->merchantId
                                              ],
                                              'No Banking Account found for the merchant: ' . $this->merchantId);
            }
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

        $this->to($bankingAccount->getBeneficiaryEmail(),
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
