<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Trace\TraceCode;
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

    protected $bankingAccount = null;

    protected $merchantId;

    protected $config;

    public function __construct(string $merchantId)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $this->config = $app['config'];

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
        else
        {
            // this will be an exception and no mail should go. Because we do not have enough data
            throw new BadRequestException(TraceCode::L2_SUBMISSION_WHITELIST_EMAIL_FAILED,
                                          null,
                                          [
                                              'merchant_id' => $merchantId
                                          ],
                                          'No Banking Account found for the merchant: ' . $merchantId);
        }
    }

    protected function addMailData()
    {

        $data = [
            'learn_more_url'                       => self::LEARN_MORE_URL,
            'support_url'                          => self::SUPPORT_URL,
            'view_dashboard_url'                   => $this->config['applications.banking_service_url'],
            BankingAccountEntity::ACCOUNT_IFSC     => $this->bankingAccount->getAccountIfsc(),
            BankingAccountEntity::ACCOUNT_NUMBER   => $this->bankingAccount->getAccountNumber(),
            BankingAccountEntity::BENEFICIARY_NAME => $this->bankingAccount->getBeneficiaryName()
        ];

        $this->with($data);

        return $this;
    }

    protected function addRecipients()
    {
        $this->to($this->bankingAccount->getBeneficiaryEmail(),
                  $this->bankingAccount->getBeneficiaryName());

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
