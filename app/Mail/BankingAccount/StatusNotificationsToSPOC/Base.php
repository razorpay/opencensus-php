<?php

namespace RZP\Mail\BankingAccount\StatusNotificationsToSPOC;

use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\Base\PublicEntity;
use RZP\Models\BankingAccount\Activation\Detail as ActivationDetail;

class Base extends Mailable
{
    protected $states;

    protected $email;

    public function __construct(array $bankingAccountStates, string $email)
    {
        parent::__construct();

        $this->states = $bankingAccountStates;

        $this->email = $email;
    }

    protected function addMailData()
    {
        $data = [];

        foreach ($this->states as $state)
        {
            $bankingAccount = $state->bankingAccount;

            array_push($data, [
                PublicEntity::MERCHANT_ID => $state->getMerchantId(),

                'businessName' => $bankingAccount->merchant->merchantDetail->getBusinessName(),

                'name' => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_NAME],

                'phoneNumber' => $bankingAccount->bankingAccountActivationDetails[ActivationDetail\Entity::MERCHANT_POC_PHONE_NUMBER],

                'lmsLink' => 'https://admin-dashboard.razorpay.com/admin/banking-accounts/bacc_'. $bankingAccount->getId(),
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
        $this->subject(static::SUBJECT);

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
