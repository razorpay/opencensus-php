<?php


namespace RZP\Models\BankingAccount\Activation\Notification;


class Event
{
    /** @var $name string */
    protected $name;

    /** @var $type string */
    protected $type;

    /** @var $properties array */
    protected $properties;

    // types
    const INFO = 'info';
    const ALERT = 'alert';

    // names
    const STATUS_CHANGE = 'status_change';
    const SUBSTATUS_CHANGE = 'substatus_change';
    const ASSIGNEE_CHANGE = 'assignee_change';
    const ACCOUNT_OPENING_WEBHOOK_DATA_AMBIGUITY = 'account_opening_webhook_data_ambiguity';
    const APPLICATION_RECEIVED = 'application_received';
    const PERSONAL_DETAILS_FILLED = 'personal_details_filled';
    const PROCESSING_DISCREPANCY_IN_DOCS = 'processing_discrepancy_in_doc';
    const RM_ASSIGNED = 'rm_assigned';
    const BANK_PARTNER_ASSIGNED = 'bank_partner_assigned';
    const BANK_PARTNER_POC_ASSIGNED = 'bank_partner_poc_assigned';

    public function __construct(string $name, string $type, array $properties)
    {
        $this->name = $name;
        $this->type = $type;
        $this->properties = $properties;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function getType()
    {
        return $this->type;
    }

    public function toArray()
    {
        return [
            'name'       => $this->name,
            'type'       => $this->type,
            'properties' => $this->properties
        ];
    }
}
