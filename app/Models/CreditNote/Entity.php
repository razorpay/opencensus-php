<?php

namespace RZP\Models\CreditNote;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Plan\Subscription;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID      = 'merchant_id';
    const CUSTOMER_ID      = 'customer_id';
    const SUBSCRIPTION_ID  = 'subscription_id';
    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const AMOUNT           = 'amount';
    const AMOUNT_AVAILABLE = 'amount_available';
    const AMOUNT_REFUNDED  = 'amount_refunded';
    const AMOUNT_ALLOCATED = 'amount_allocated';
    const CURRENCY         = 'currency';
    const STATUS           = 'status';

    const ACTION       = 'action';
    const INVOICES     = 'invoices';
    const INVOICE_ID   = 'invoice_id';
    const SUBSCRIPTION = 'subscription';

    protected $entity = 'creditnote';

    protected $generateIdOnCreate = true;

    protected static $sign = 'crnt';

    protected $visible = [
        self::ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::SUBSCRIPTION_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::AMOUNT_AVAILABLE,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_ALLOCATED,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $fillable = [
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::CURRENCY,
        self::SUBSCRIPTION_ID,
    ];

    protected $defaults = [
        self::AMOUNT_REFUNDED  => 0,
        self::AMOUNT_ALLOCATED => 0,
        self::STATUS           => Status::CREATED,
    ];

    protected $public = [
        self::ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::SUBSCRIPTION_ID,
        self::NAME,
        self::DESCRIPTION,
        self::AMOUNT,
        self::AMOUNT_AVAILABLE,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_ALLOCATED,
        self::CURRENCY,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $amounts = [
        self::AMOUNT,
        self::AMOUNT_AVAILABLE,
        self::AMOUNT_REFUNDED,
        self::AMOUNT_ALLOCATED,
    ];

    protected $publicSetters = [
        self::ID,
        self::SUBSCRIPTION_ID,
        self::CUSTOMER_ID,
    ];

    protected static $generators = [
        self::AMOUNT_AVAILABLE,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function creditNoteInvoices()
    {
        return $this->hasMany('RZP\Models\CreditNote\Invoice\Entity', 'creditnote_id');
    }

    /**
     * Defines a polymorphic relation with entities
     * implementing a morphMany association on the
     * 'source' key
     */

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getAmountAvailable()
    {
        return $this->getAttribute(self::AMOUNT_AVAILABLE);
    }

    public function getAmountRefunded()
    {
        return $this->getAttribute(self::AMOUNT_REFUNDED);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getSubscriptionId()
    {
        return $this->getAttribute(self::SUBSCRIPTION_ID);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function setAmountAvailable(int $amount)
    {
        $this->setAttribute(self::AMOUNT_AVAILABLE, $amount);
    }

    public function setAmountRefunded(int $amount)
    {
        $this->setAttribute(self::AMOUNT_REFUNDED, $amount);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function calculateAndSetAmountRefundedAndAvailable($refundedAmount)
    {
        $this->setAmountAvailable($this->getAmountAvailable() - $refundedAmount);

        $this->setAmountRefunded($this->getAmountRefunded() + $refundedAmount);
    }

    public function setAppropriateStatus()
    {
        if ($this->getAmountAvailable() === 0)
        {
            $this->setStatus(Status::PRCOESSED);
        }

        if (($this->getAmountAvailable() > 0 === true) and
            ($this->getAmountAvailable() < $this->getAmount() === true))
        {
            $this->setStatus(Status::PARTIALLY_PROCESSED);
        }
    }

    protected function setPublicSubscriptionIdAttribute(array & $array)
    {
        $subscriptionId = $this->getAttribute(self::SUBSCRIPTION_ID);

        if ($subscriptionId !== null)
        {
            $array[self::SUBSCRIPTION_ID] = Subscription\Entity::getSignedIdOrNull($subscriptionId);
        }
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function generateAmountAvailable(array $input)
    {
        $this->setAttribute(self::AMOUNT_AVAILABLE, $input[self::AMOUNT]);
    }
}
