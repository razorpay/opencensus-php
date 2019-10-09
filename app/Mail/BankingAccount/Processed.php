<?php

namespace RZP\Mail\BankingAccount;

use App;
use RZP\Mail\Base\Mailable;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Models\BankingAccount\Entity;

class Processed extends Mailable
{
    const TEMPLATE_PATH = 'emails.banking_account.notify_status_processed';

    protected $bankingAccount;

    protected $config;

    /**
     * Cancelled constructor.
     * @param Entity $bankingAccount
     */
    public function __construct(Entity $bankingAccount)
    {
        parent::__construct();

        $this->bankingAccount = $bankingAccount;

        $this->config = App::getFacadeRoot()['config'];
    }

    protected function addRecipients()
    {
        $toEmail = $this->bankingAccount->merchant->getEmail();

        $toName = $this->bankingAccount->merchant->getName();

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::RAZORPAY_X];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view(self::TEMPLATE_PATH);

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'Your RazorpayX Current Account is Processed';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {

        $data = [
            'view_dashboard_url' => $this->config['applications.razorx.url'],
            'merchant_name'      => $this->bankingAccount->getBeneficiaryName(),
            'account_number'     => $this->bankingAccount->getAccountNumber(),
            'ifsc_code'          => $this->bankingAccount->getAccountIfsc()
        ];

        $this->with($data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ACCOUNT_STATUS_UPDATED;
    }
}
