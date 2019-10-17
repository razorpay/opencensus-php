<?php

namespace RZP\Mail\Merchant\RazorpayX;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;
use RZP\Models\Merchant\Entity;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class InstantActivation extends Mailable
{
    const TEMPLATE_PATH        = 'emails.merchant.razorpayx.instant_activation';

    const LEARN_MORE_URL       = '';

    const GUIDE_TO_GO_LIVE_URL = '';

    const SUPPORT_URL          = '';

    const SUBJECT = 'One step away from starting transactions on RazorpayX';

    protected $bankingAccount = null;

    /**
     * InstantActivation constructor.
     * @param Entity $merchant
     */
    public function __construct(Entity $merchant)
    {
        parent::__construct();

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

    protected function addMailData()
    {

        $data = [
            'learn_more_url'                       => self::LEARN_MORE_URL,
            'guide_to_go_live_url'                 => self::GUIDE_TO_GO_LIVE_URL,
            'support_url'                          => self::SUPPORT_URL,
            BankingAccountEntity::ACCOUNT_IFSC     => '',
            BankingAccountEntity::ACCOUNT_NUMBER   => '',
            BankingAccountEntity::BENEFICIARY_NAME => ''
        ];

        $data = $this->addBankingAccountData($this->bankingAccount, $data);

        $this->with($data);

        return $this;
    }

    protected function addRecipients()
    {
        if ( empty($this->bankingAccount) === false )
        {
            $this->to($this->bankingAccount->getBeneficiaryEmail());
        }

        $this->to('anubhav.shrivastava@razorpay.com');

        return $this;
    }

    protected function addBankingAccountData(BankingAccountEntity $bankingAccount, & $data)
    {
        $data[BankingAccountEntity::ACCOUNT_IFSC] = $bankingAccount->getAccountIfsc();

        $data[BankingAccountEntity::ACCOUNT_NUMBER] = $bankingAccount->getAccountNumber();

        $data[BankingAccountEntity::BENEFICIARY_NAME] = $bankingAccount->getBeneficiaryName();
    }

    protected function addSubject()
    {
        $this->subject(self::SUBJECT);

        return $this;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                    Constants::HEADERS[Constants::SUPPORT]);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }

}