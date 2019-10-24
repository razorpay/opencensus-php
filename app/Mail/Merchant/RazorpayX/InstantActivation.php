<?php

namespace RZP\Mail\Merchant\RazorpayX;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class InstantActivation extends Mailable
{
    const TEMPLATE_PATH        = 'emails.merchant.razorpayx.instant_activation_mail';

    const LEARN_MORE_URL       = '';

    const FILL_KYC_URL         = '';

    const GUIDE_TO_GO_LIVE_URL = '';

    const SUPPORT_URL          = '';

    const SUBJECT              = 'One step away from starting transactions on RazorpayX';

    protected $bankingAccount;

    protected $config;

    /**
     * InstantActivation constructor.
     * @param string $merchantId
     * @throws BadRequestException
     */
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
            throw new BadRequestException(TraceCode::INSTANT_ACTIVATION_EMAIL_FAILED,
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
            'guide_to_go_live_url'                 => self::GUIDE_TO_GO_LIVE_URL,
            'support_url'                          => self::SUPPORT_URL,
            'fill_kyc_url'                         => self::FILL_KYC_URL,
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
