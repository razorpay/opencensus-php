<?php

namespace RZP\Mail\Merchant\RazorpayX;

use App;
use RZP\Error\ErrorCode;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class InstantActivation extends Mailable
{
    const TEMPLATE_PATH        = 'emails.merchant.razorpayx.instant_activation_mail';

    const LEARN_MORE_URL       = 'https://razorpay.com/docs/razorpayx/';

    const FILL_KYC_URL         = 'https://x.razorpay.com/activation';

    const GUIDE_TO_GO_LIVE_URL = 'https://razorpay.com/docs/razorpayx/api/';

    const SUPPORT_URL          = 'https://x.razorpay.com/?support=ticket';

    const SUBJECT              = 'One step away from starting transactions on RazorpayX';

    protected $bankingAccount;

    protected $merchant;

    protected $merchantId;

    /**
     * InstantActivation constructor.
     * @param string $merchantId
     */
    public function __construct(string $merchantId)
    {
        parent::__construct();

        $this->merchantId = $merchantId;
    }

    protected function getMerchant()
    {
        if ($this->merchant === null)
        {
            $repo = App::getFacadeRoot()['repo'];

            $this->merchant = $repo->merchant->find($this->merchantId);
        }

        return $this->merchant;
    }

    protected function getBankingAccount()
    {
        if (empty($this->bankingAccount) === true)
        {
            $merchant = $this->getMerchant();

            $this->bankingAccount = $merchant->vaBankingAccounts()->first();
        }

        return $this->bankingAccount;
    }

    protected function addMailData()
    {
        $bankingAccount = $this->getBankingAccount();

        $config = App::getFacadeRoot()['config'];

        $data = [
            'learn_more_url'                       => self::LEARN_MORE_URL,
            'guide_to_go_live_url'                 => self::GUIDE_TO_GO_LIVE_URL,
            'support_url'                          => self::SUPPORT_URL,
            'fill_kyc_url'                         => self::FILL_KYC_URL,
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
