<?php

namespace RZP\Mail\BankingAccount;

use Carbon\Carbon;

use RZP\Models\Merchant;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;


class XProActivation extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.x_pro_activation';

    const MAIL_TAG      = MailTags::BANKING_ACCOUNT_X_PRO_ACTIVATION;

    const SUBJECT       = 'Merchant with MID: %s has requested CA on %s';

    /**
     * This email is sent to ops to notify them about the interest merchant has shown in
     * X Pro plan, currently that is RBL current account
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->toEmail      = Constants::X_SUPPORT;
        $this->fromEmail    = Constants::NOREPLY;
        $this->replyToEmail = Constants::NOREPLY;

        parent::__construct($data);
    }

    protected function getSubject()
    {
        $merchantId = $this->data[Entity::MERCHANT][Entity::ID];

        $createdAt = $this->data[Entity::CREATED_AT];

        $dateTime = Carbon::createFromTimestamp($createdAt, Timezone::IST)->format('d-M-y H:i');

        return sprintf(self::SUBJECT, $merchantId, $dateTime);
    }

    protected function getMailData()
    {
        $data = $this->data;

        $merchant = $data[Entity::MERCHANT];

        $createdAt = $data[Entity::CREATED_AT];

        $dateTime = Carbon::createFromTimestamp($createdAt, Timezone::IST)->format('d-M-y H:i');

        $data = [
            'internal_reference_number' => $data[Entity::BANK_REFERENCE_NUMBER],
            'merchant_name'             => $merchant[Merchant\Entity::NAME],
            'merchant_id'               => $merchant[Merchant\Entity::ID],
            'merchant_email'            => $merchant[Merchant\Entity::EMAIL],
            'business_category'         => $merchant[Merchant\Entity::CATEGORY],
            'pincode'                   => $data[Entity::PINCODE],
            'application_date'          => $dateTime,
        ];

        return $data;
    }
}
