<?php


namespace RZP\Mail\BankingAccount\Activation;

use RZP\Models\BankingAccount\Activation\Notification\Constants;

class AssigneeChange extends Base
{
    const SUBJECT = "RazorpayX LMS | Assignee for CA of Merchant - %s has been changed to %s(%s)";

    protected $merchantBusinessName;

    protected $newAssigneeTeam;

    protected $newAssigneeName;

    public function __construct(string $bankingAccountId, array $eventDetails)
    {
        parent::__construct($bankingAccountId, $eventDetails);

        $this->merchantBusinessName = $this->bankingAccount->merchant->merchantDetail->getBusinessName();

        $this->newAssigneeTeam = $eventDetails[Constants::PROPERTIES][Constants::NEW_ASSIGNEE_TEAM];

        $this->newAssigneeName = $eventDetails[Constants::PROPERTIES][Constants::NEW_ASSIGNEE_NAME];
    }

    protected function getSubject()
    {
        return sprintf(self::SUBJECT, $this->merchantBusinessName, $this->newAssigneeTeam, $this->newAssigneeName);
    }

    protected function addMailData()
    {
        $data = [
            'body' => 'This is to notify that ' . $this->newAssigneeName . ' and team '. $this->newAssigneeTeam .' is the new assignee for Current Account for Merchant ' . $this->merchantBusinessName . '.'
        ];

        $this->with($data);

        return parent::addMailData();
    }
}
