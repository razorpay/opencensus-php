<?php


namespace RZP\Mail\BankingAccount\Activation;

use RZP\Models\BankingAccount\Status;
use RZP\Models\BankingAccount\Activation\Notification\Constants;

class StatusChange extends Base
{
    const SUBJECT = "RazorpayX LMS | %s's CA has been %s";

    protected $merchantBusinessName;

    protected $newStatus;

    public function __construct(string $bankingAccountId, array $eventDetails)
    {
        parent::__construct($bankingAccountId, $eventDetails);

        $this->merchantBusinessName = $this->bankingAccount->merchant->merchantDetail->getBusinessName();

        $this->newStatus = Status::transformFromInternalToExternal($eventDetails[Constants::PROPERTIES][Constants::NEW_STATUS]);
    }

    protected function getSubject()
    {
        return sprintf(self::SUBJECT, $this->merchantBusinessName, $this->newStatus);
    }

    protected function addMailData()
    {
        $data = [
            'body' => 'This is to notify that Current Account for Merchant ' . $this->merchantBusinessName . ' has been ' . $this->newStatus
        ];

        $this->with($data);

        return parent::addMailData();
    }
}
