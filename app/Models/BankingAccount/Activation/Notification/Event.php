<?php


namespace RZP\Models\BankingAccount\Activation\Notification;


class Event
{
    /** @var $name string */
    protected $name;

    /** @var $type string */
    protected $type;

    /** @var $name array */
    protected $properties;

    // types
    const INFO = 'info';
    const ALERT = 'alert';

    // names
    const STATUS_CHANGE = 'status_change';
    const SUBSTATUS_CHANGE = 'substatus_change';
    const ASSIGNEE_CHANGE = 'assignee_change';

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
