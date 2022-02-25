<?php

namespace RZP\Mail\BankingAccount;


use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;


class CurrentAccount extends Base
{
    const TEMPLATE_PATH = 'emails.banking_account.icici_request';

    const MAIL_TAG = MailTags::ICICI_CURRENT_ACCOUNT;

    const SUBJECT = 'Merchant with MID: %s has requested ICICI CA on %s';

    /**
     * This email is sent to ops to notify them about the interest merchant has shown in
     * ICICI Current Account
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->toEmail = Constants::X_SUPPORT;
        $this->fromEmail = Constants::NOREPLY;
        $this->replyToEmail = Constants::NOREPLY;

        parent::__construct($data);
    }

    protected function getSubject()
    {
        $merchantId = $this->data['merchant_id'];

        $now = Carbon::now()->getTimestamp();

        $dateTime = Carbon::createFromTimestamp($now, Timezone::IST)->format('d-M-y H:i');

        return sprintf(self::SUBJECT, $merchantId, $dateTime);
    }

    protected function getMailData()
    {
        $data = $this->data;

        $data = [
            'merchant_id'                        => $data['merchant_id'],
            'merchant_name'                      => $data['merchant_name'],
            'merchant_email'                     => $data['CA_Preferred_Email'],
            'merchant_phone'                     => $data['CA_Preferred_Phone'],
            'constitution'                       => $data['constitution'],
            'pincode'                            => $data['pincode'],
            'sales_team'                         => $data['sales_team'],
            'account_manager_name'               => $data['account_manager_name'],
            'account_manager_email'              => $data['account_manager_email'],
            'account_manager_phone'              => $data['account_manager_phone'],
        ];

        return $data;
    }
}
