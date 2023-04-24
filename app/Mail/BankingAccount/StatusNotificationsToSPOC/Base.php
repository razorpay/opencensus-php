<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

use Carbon\Carbon;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount;
use RZP\Models\Base\PublicEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class Base extends Mailable
{
    protected $bankingAccounts;

    protected $email;

    public function __construct(array $bankingAccounts, string $email)
    {
        parent::__construct();

        $this->bankingAccounts = $bankingAccounts;

        $this->email = $email;
    }

    protected function addMailData()
    {
        $data = [];

        foreach ($this->bankingAccounts as $bankingAccount)
        {
            array_push($data, [
                PublicEntity::MERCHANT_ID => $bankingAccount[BankingAccount\Entity::MERCHANT_ID],

                'businessName' => array_get($bankingAccount,BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS . '.' . BankingAccount\Activation\Detail\Entity::BUSINESS_NAME,''),

                'name' => array_get($bankingAccount,BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS . '.' . BankingAccount\Activation\Detail\Entity::MERCHANT_POC_NAME,''),

                'phoneNumber' => array_get($bankingAccount,BankingAccount\Entity::BANKING_ACCOUNT_ACTIVATION_DETAILS . '.' . BankingAccount\Activation\Detail\Entity::MERCHANT_POC_PHONE_NUMBER,''),

                'lmsLink' => 'https://admin-dashboard.razorpay.com/admin/banking-accounts/bacc_'. $bankingAccount[BankingAccount\Entity::ID],
            ]);
        }

        $this->with(['data' => $data]);

        return $this;
    }

    protected function addRecipients()
    {
        $toEmail = $this->email;

        $this->to($toEmail);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(static::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        $this->subject(static::SUBJECT . ' | ' . $date);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::BANKING_ACCOUNT_STATUS_UPDATED_TO_SPOC;
    }

    protected function addSender()
    {
        $this->from(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                    Constants::HEADERS[Constants::NOREPLY]);

        return $this;
    }

    protected function addReplyTo()
    {
        $this->replyTo(Constants::MAIL_ADDRESSES[Constants::NOREPLY],
                       Constants::HEADERS[Constants::NOREPLY]);

        return $this;
    }
}
