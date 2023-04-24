<?php


namespace RZP\Mail\BankingAccount\Activation;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Notification\Event;

abstract class BaseV2 extends Mailable
{
    const SUBJECT       = '';

    /** @var array $bankingAccount */
    protected $bankingAccount;

    protected $eventDetails;

    /**
     * @param array $bankingAccount
     * @param array $eventDetails
     */
    public function __construct(array $bankingAccount, array $eventDetails)
    {
        parent::__construct();

        $this->bankingAccount = $bankingAccount;

        $this->eventDetails = $eventDetails;
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.banking_account.internal_notification');

        return $this;
    }

    protected function addMailData()
    {
        $data = $this->viewData;

        $data['internal_reference_number'] = $this->bankingAccount[Entity::BANK_REFERENCE_NUMBER];

        $merchant = app('repo')->merchant->findOrFail($this->bankingAccount[Entity::MERCHANT_ID]);
        $data['merchant_name']             = $merchant->getName();
        $data['merchant_id']               = $merchant->getId();
        $data['admin_dashboard_link']      = 'https://dashboard.razorpay.com/admin#/app/banking-accounts/bacc_' .
            $this->bankingAccount[Entity::ID];

        $this->with($data);

        return $this;
    }
}
