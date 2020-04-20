<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID  = 'payment_id';
    const FLOW        = 'flow';
    const TYPE        = 'type';
    const START_TIME  = 'start_time';
    const END_TIME    = 'end_time';
    const VPA         = 'vpa';
    const EXPIRY_TIME = 'expiry_time';
    const PROVIDER    = 'provider';

    // Constants
    const UPI_METADATA = 'upi_metadata';

    // 90 days, in seconds
    const DEFAULT_OTM_EXECUTION_RANGE = 7776000;

    protected $entity = 'upi_metadata';

    protected $generateIdOnCreate = false;

    protected $primaryKey = self::PAYMENT_ID;

    protected $fillable = [
        self::FLOW,
        self::TYPE,
        self::START_TIME,
        self::END_TIME,
        self::VPA,
        self::EXPIRY_TIME,
        self::PROVIDER,
    ];

    protected $visible = [
        self::PAYMENT_ID,
        self::FLOW,
        self::TYPE,
        self::START_TIME,
        self::END_TIME,
        self::VPA,
        self::EXPIRY_TIME,
        self::PROVIDER,
    ];

    protected $public = [
        self::PAYMENT_ID,
        self::FLOW,
        self::TYPE,
        self::START_TIME,
        self::END_TIME,
        self::VPA,
        self::EXPIRY_TIME,
        self::PROVIDER,
    ];

    protected $defaults = [
        self::FLOW => null,
        self::TYPE => null,
        self::VPA  => null,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::START_TIME,
        self::END_TIME,
    ];


    // -------------- RELATIONS ----------------

    public function payment()
    {
        return $this->belongsTo(Payment\Entity::class);
    }

    // -------------- END RELATIONS --------------

    // --------------- GETTERS -------------------

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getFlow()
    {
        return $this->getAttribute(self::FLOW);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getStartTime()
    {
        return $this->getAttribute(self::START_TIME);
    }

    public function getEndTime()
    {
        return $this->getAttribute(self::END_TIME);
    }

    public function getVpa()
    {
        return $this->getAttribute(self::VPA);
    }

    public function getExpiryTime()
    {
        return $this->getAttribute(self::EXPIRY_TIME);
    }

    public function getProvider()
    {
        return $this->getAttribute(self::PROVIDER);
    }

    // --------------  END GETTERS ----------------

    // -------------- SETTERS --------------------

    public function setFlow(string $flow)
    {
        return $this->setAttribute(self::FLOW, $flow);
    }

    public function setType(string $type)
    {
        return $this->setAttribute(self::TYPE, $type);
    }

    public function setStartTime(int $time)
    {
        return $this->setAttribute(self::START_TIME, $time);
    }

    public function setEndTime(int $time)
    {
        return $this->setAttribute(self::END_TIME, $time);
    }

    public function setVpa(string $vpa)
    {
        return $this->setAttribute(self::VPA, $vpa);
    }

    public function setExpiryTime(int $expiryTime)
    {
        return $this->setAttribute(self::EXPIRY_TIME, $expiryTime);
    }

    public function setProvider(string $provider)
    {
        return $this->setAttribute(self::PROVIDER, $provider);
    }

    // -------------- END SETTERS ----------------

    public function associatePayment(Payment\Entity $payment)
    {
        return $this->payment()->associate($payment);
    }

    // Helpers

    public function isOtm(): bool
    {
        return ($this->getAttribute(self::TYPE) === Type::OTM);
    }

    /**
     * Returns if the given timestamp is in range of start time
     * and end time.
     * @param int $timestamp
     * @return bool
     */
    public function inTimeRange(int $timestamp): bool
    {
        return (($this->getStartTime() <= $timestamp) and
                ($timestamp <= $this->getEndTime()));
    }

    /**
     * @return int
     */
    public function getTimeRange()
    {
        return $this->getAttribute(self::END_TIME) - $this->getAttribute(self::START_TIME);
    }

    public static function isValidFlow($flow)
    {
        return in_array($flow, [Flow::INTENT, Flow::OMNICHANNEL, Flow::COLLECT]);
    }
}
