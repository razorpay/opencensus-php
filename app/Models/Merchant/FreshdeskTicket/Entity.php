<?php

namespace RZP\Models\Merchant\FreshdeskTicket;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const TICKET_ID         = 'ticket_id';
    const TYPE              = 'type';
    const TICKET_DETAILS    = 'ticket_details';
    const CUSTOMER_EMAIL    = 'email';
    const STATUS            = 'status';
    const SUBJECT           = 'subject';
    const DESCRIPTION       = 'description';
    const CREATED_BY        = 'created_by';
    const CREATED_AT        = 'created_at';
    const UPDATED_AT        = 'updated_at';

    protected $entity = 'merchant_freshdesk_tickets';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::TICKET_ID,
        self::TYPE,
        self::TICKET_DETAILS,
        self::CREATED_BY,
        self::STATUS
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::TICKET_ID,
        self::TYPE,
        self::TICKET_DETAILS,
        self::CREATED_BY,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::TICKET_DETAILS   => 'json',
    ];

    protected $public = [
        self::ID,
        self::MERCHANT_ID,
        self::TICKET_ID,
        self::TYPE,
        self::TICKET_DETAILS,
        self::STATUS,
        self::CREATED_BY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function merchant()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
    }

    public function getTicketId() : string
    {
        return $this->getAttribute(self::TICKET_ID);
    }

    public function getTicketType() : string
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getTicketDetails()
    {
        return $this->getAttribute(self::TICKET_DETAILS);
    }

    public function setTicketId(string $ticketId)
    {
        $this->setAttribute(self::TICKET_ID, $ticketId);
    }

    public function setTicketType(string $type)
    {
        $this->setAttribute(self::TYPE, $type);
    }

    public function setTicketDetails($ticketDetails)
    {
        $this->setAttribute(self::TICKET_DETAILS, $ticketDetails);
    }

    public function getFdInstance()
    {
        $ticketDetails = $this->getAttribute(self::TICKET_DETAILS);

        return $ticketDetails[Constants::FD_INSTANCE];
    }

    public function getCreatedBy()
    {
        return $this->getAttribute(self::CREATED_BY);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

}
