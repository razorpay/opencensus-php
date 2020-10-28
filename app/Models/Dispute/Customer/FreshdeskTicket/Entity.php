<?php

namespace RZP\Models\Dispute\Customer\FreshdeskTicket;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const REASON_CODE      = 'reason_code';
    const PAYMENT_ID       = 'payment_id';
    const TICKET_ID        = 'ticket_id';
    const CUSTOMER_EMAIL   = 'customer_email';
    const CUSTOMER_NAME    = 'customer_name';
    const SUBCATEGORY      = 'subcategory';

    protected $fillable =[
        self::REASON_CODE,
        self::PAYMENT_ID,
        self::TICKET_ID,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_NAME,
        self::SUBCATEGORY,
    ];

    protected static $modifiers = [
        self::SUBCATEGORY,
    ];

    public function getReasonCode()
    {
        return $this->getAttribute(self::REASON_CODE);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getTicketId()
    {
        return $this->getAttribute(self::TICKET_ID);
    }

    public function getCustomerEmail()
    {
        return $this->getAttribute(self::CUSTOMER_EMAIL);
    }

    public function getCustomerName()
    {
        return $this->getAttribute(self::CUSTOMER_NAME);
    }

    public function getSubcategory()
    {
        return $this->getAttribute(self::SUBCATEGORY);
    }


    // ----------------------------------- MODIFIERS -----------------------------------

    protected function modifySubcategory(& $input)
    {
        if ($input[self::SUBCATEGORY] === Subcategory::DISPUTE_A_PAYMENT_FD)
        {
            $input[self::SUBCATEGORY] = Subcategory::DISPUTE_A_PAYMENT;
        }
        else if ($input[self::SUBCATEGORY] === Subcategory::REPORT_FRAUD_FD)
        {
            $input[self::SUBCATEGORY] = Subcategory::REPORT_FRAUD;
        }
    }

}
