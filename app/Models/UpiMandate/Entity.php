<?php

namespace RZP\Models\UpiMandate;

use RZP\Models\Base;
use RZP\Models\Order;
use RZP\Models\Merchant;
use RZP\Models\Customer;

class Entity extends Base\PublicEntity
{
    const ORDER_ID             = 'order_id';
    const MAX_AMOUNT           = 'max_amount';
    const TOKEN_ID             = 'token_id';
    const CUSTOMER_ID          = 'customer_id';
    const MERCHANT_ID          = 'merchant_id';
    const STATUS               = 'status';
    const FREQUENCY            = 'frequency';
    const RECURRING_TYPE       = 'recurring_type';
    const RECURRING_VALUE      = 'recurring_value';
    const START_TIME           = 'start_time';
    const END_TIME             = 'end_time';
    const RECEIPT              = 'receipt';
    const UMN                  = 'umn';
    const RRN                  = 'rrn';
    const NPCI_TXN_ID          = 'npci_txn_id';
    const GATEWAY_DATA         = 'gateway_data';
    const USED_COUNT           = 'used_count';
    const LATE_CONFIRMED       = 'late_confirmed';
    const CONFIRMED_AT         = 'confirmed_at';
    const SEQUENCE_NUMBER      = 'sequence_number';

    // Input keys
    const VPA                  = 'vpa';
    const FLOW                 = 'flow';

    protected $entity = 'upi_mandate';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MAX_AMOUNT,
        self::FREQUENCY,
        self::RECURRING_TYPE,
        self::RECURRING_VALUE,
        self::START_TIME,
        self::END_TIME,
        self::RECEIPT,
        self::UMN,
        self::RRN,
        self::NPCI_TXN_ID,
        self::GATEWAY_DATA,
        self::CONFIRMED_AT,
    ];

    protected $public = [
        self::ID,
        self::ORDER_ID,
        self::TOKEN_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::MAX_AMOUNT,
        self::FREQUENCY,
        self::RECURRING_TYPE,
        self::RECURRING_VALUE,
        self::START_TIME,
        self::END_TIME,
        self::RECEIPT,
        self::UMN,
        self::RRN,
        self::NPCI_TXN_ID,
        self::USED_COUNT,
        self::LATE_CONFIRMED,
        self::CONFIRMED_AT,
        self::CREATED_AT,
        self::SEQUENCE_NUMBER,
    ];

    protected $visible = [
        self::ID,
        self::ORDER_ID,
        self::TOKEN_ID,
        self::CUSTOMER_ID,
        self::MERCHANT_ID,
        self::STATUS,
        self::MAX_AMOUNT,
        self::FREQUENCY,
        self::RECURRING_TYPE,
        self::RECURRING_VALUE,
        self::START_TIME,
        self::END_TIME,
        self::RECEIPT,
        self::UMN,
        self::RRN,
        self::NPCI_TXN_ID,
        self::GATEWAY_DATA,
        self::USED_COUNT,
        self::LATE_CONFIRMED,
        self::CONFIRMED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::SEQUENCE_NUMBER,
    ];

    protected $defaults = [
        self::STATUS            => 'created',
        self::LATE_CONFIRMED    => false,
        self::USED_COUNT        => 0,
    ];

    protected $appends = [
        self::SEQUENCE_NUMBER,
    ];

    protected $dates = [
        self::CONFIRMED_AT,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::GATEWAY_DATA      => 'array',
        self::LATE_CONFIRMED    => 'boolean',
        self::USED_COUNT        => 'integer',
        self::SEQUENCE_NUMBER   => 'integer',
    ];

    protected $ignoredRelations = [
        "order"
    ];

    // Relations
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function token()
    {
        return $this->belongsTo(Customer\Token\Entity::class);
    }

    public function order()
    {
        return $this->belongsTo(Order\Entity::class);
    }

    // Setters
    public function setTokenId(string $tokenId)
    {
        $this->setAttribute(self::TOKEN_ID, $tokenId);
    }

    public function setCustomerId(string $customerId)
    {
        $this->setAttribute(self::CUSTOMER_ID, $customerId);
    }

    public function setStatus(string $status)
    {
        Status::validateUpiMandateStatus($status);

        if ($status === Status::CONFIRMED)
        {
            $this->setAttribute(self::CONFIRMED_AT, $this->freshTimestamp());
        }

        $this->setAttribute(self::STATUS, $status);
    }

    public function setLateConfirmed(bool $value)
    {
        return $this->setAttribute(self::LATE_CONFIRMED, $value);
    }

    public function setUmn($value)
    {
        return $this->setAttribute(self::UMN, $value);
    }

    public function setRrn($value)
    {
        return $this->setAttribute(self::RRN, $value);
    }

    public function setNpciTxnId($value)
    {
        return $this->setAttribute(self::NPCI_TXN_ID, $value);
    }

    public function setGatewayData($value)
    {
        return $this->setAttribute(self::GATEWAY_DATA, $value);
    }

    public function setVpa($value)
    {
        if (is_null($value) === true)
        {
            return;
        }
        // As of now we do not have any other field to same VPA for Mandate, which is very
        // important for future use cases, thus we will get vpa saved in Gateway Data for now
        $gatewayData = $this->getGatewayData();

        $gatewayData[self::VPA] = $value;

        return $this->setGatewayData($gatewayData);
    }

    /**
     * Sets the FLOW in Gateway Data
     *
     * @param  string|null $flow
     * @return mixed|Entity|void
     */
    public function setFlow(?string $flow)
    {
        if (is_null($flow) === true)
        {
            return;
        }

        // As of now we do not have any other field to save FLOW for Mandate, which is very
        // important for analytics and debugging, thus we will save FLOW in Gateway Data for now
        $gatewayData = $this->getGatewayData();

        $gatewayData[self::FLOW] = $flow;

        return $this->setGatewayData($gatewayData);
    }

    public function incrementUsedCount()
    {
        $current = (int) $this->getUsedCount();

        if (($current === 1) and
            (in_array($this->getStatus(), [Status::CREATED, Status::REJECTED]) === true))
        {
            return $this;
        }

        return $this->setAttribute(self::USED_COUNT, ($current + 1));
    }

    // Getters
    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getFrequency()
    {
        return $this->getAttribute(self::FREQUENCY);
    }

    public function getTokenId()
    {
        return $this->getAttribute(self::TOKEN_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getEndTime()
    {
        return $this->getAttribute(self::END_TIME);
    }

    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
    }

    public function getUsedCount()
    {
        return $this->getAttribute(self::USED_COUNT);
    }

    public function getConfirmedAt()
    {
        return $this->getAttribute(self::CONFIRMED_AT);
    }

    public function getUmn()
    {
        return $this->getAttribute(self::UMN);
    }

    public function getRecurringType()
    {
        return $this->getAttribute(self::RECURRING_TYPE);
    }

    public function getRecurringValue()
    {
        return $this->getAttribute(self::RECURRING_VALUE);
    }

    public function getSequenceNumberAttribute()
    {
        if ($this->getFrequency() === Frequency::AS_PRESENTED)
        {
            return $this->getUsedCount();
        }

        $sequenceNumber = new SequenceNumber($this->getConfirmedAt(), $this->freshTimestamp());

        return $sequenceNumber->generate($this->getFrequency());
    }

    public function getOrderAttribute()
    {
        $order = null;

        if ($this->relationLoaded('order') === true)
        {
            $order = $this->getRelation('order');
        }

        if ($order !== null)
        {
            return $order;
        }

        $order = $this->order()->with('offers')->first();

        if (empty($order) === false)
        {
            return $order;
        }

        if (empty($this[self::ORDER_ID]) === true)
        {
            return null;
        }

        $order = (new Order\Repository)->findOrFailPublic('order_'.$this[self::ORDER_ID]);

        $this->order()->associate($order);

        return $order;
    }

    public function toArrayTrace(): array
    {
        return array_only($this->toArray(), [
            self::FREQUENCY,
            self::USED_COUNT,
            self::START_TIME,
            self::END_TIME,
            self::RECURRING_VALUE,
            self::RECURRING_TYPE,
            self::SEQUENCE_NUMBER,
        ]);
    }
}
