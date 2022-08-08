<?php


namespace RZP\Mail\BankingAccount\Activation;

use App;

use RZP\Mail\Base\Mailable;
use RZP\Models\BankingAccount\Entity;
use RZP\Models\BankingAccount\Activation\Notification\Event;

abstract class Base extends Mailable
{
    const SUBJECT       = '';

    /** @var Entity $bankingAccount */
    protected $bankingAccount;

    protected $eventDetails;

    /**
     * @param string $bankingAccountId
     * @param array $eventDetails
     */
    public function __construct(string $bankingAccountId, array $eventDetails)
    {
        parent::__construct();

        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $this->bankingAccount = $repo->banking_account->find($bankingAccountId);

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

        $data['internal_reference_number'] = $this->bankingAccount->getBankReferenceNumber();
        $data['merchant_name']             = $this->bankingAccount->merchant->getName();
        $data['merchant_id']               = $this->bankingAccount->merchant->getId();
        $data['admin_dashboard_link']      = $this->bankingAccount->getDashboardEntityLink();

        $this->with($data);

        return $this;
    }
}
