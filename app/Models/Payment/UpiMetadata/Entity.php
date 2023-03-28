<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;
use RZP\Models\Payment;

class Entity extends Base\PublicEntity
{
    const PAYMENT_ID                = 'payment_id';
    const FLOW                      = 'flow';
    const TYPE                      = 'type';
    const MODE                      = 'mode';
    const START_TIME                = 'start_time';
    const END_TIME                  = 'end_time';
    const VPA                       = 'vpa';
    const EXPIRY_TIME               = 'expiry_time';
    const PROVIDER                  = 'provider';
    const REFERENCE                 = 'reference';
    const NPCI_TXN_ID               = 'npci_txn_id';
    const UMN                       = 'umn';
    const RRN                       = 'rrn';
    const INTERNAL_STATUS           = 'internal_status';
    const REMINDER_ID               = 'reminder_id';
    const REMIND_AT                 = 'remind_at';
    const APP                       = 'app';
    const ORIGIN                    = 'origin';
    const FLAG                      = 'flag';

    // Constants
    const UPI_METADATA              = 'upi_metadata';
    const PAYMENT                   = 'payment';

    // 90 days, in seconds
    const DEFAULT_OTM_EXECUTION_RANGE = 7776000;

    // 90 second buffer for reminder service request
    const DEFAULT_BUFFER_FOR_REMINDER = 90;

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
        self::REFERENCE,
        self::NPCI_TXN_ID,
        self::UMN,
        self::RRN,
        self::REMIND_AT,
        self::APP,
        self::ORIGIN,
        self::FLAG,
        self::MODE,
    ];

    protected $visible = [
        self::PAYMENT_ID,
        self::FLOW,
        self::TYPE,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::VPA,
        self::EXPIRY_TIME,
        self::PROVIDER,
        self::REFERENCE,
        self::NPCI_TXN_ID,
        self::UMN,
        self::RRN,
        self::INTERNAL_STATUS,
        self::REMINDER_ID,
        self::REMIND_AT,
        self::APP,
        self::ORIGIN,
        self::FLAG,
    ];

    protected $public = [
        self::PAYMENT_ID,
        self::FLOW,
        self::TYPE,
        self::MODE,
        self::START_TIME,
        self::END_TIME,
        self::VPA,
        self::EXPIRY_TIME,
        self::UMN,
    ];

    protected $defaults = [
        self::FLOW => null,
        self::TYPE => null,
        self::VPA  => null,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REMIND_AT,
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

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
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

    public function getReference()
    {
        return $this->getAttribute(self::REFERENCE);
    }

    public function getNpciTxnId()
    {
        return $this->getAttribute(self::NPCI_TXN_ID);
    }

    public function getUmn()
    {
        return $this->getAttribute(self::UMN);
    }

    public function getRrn()
    {
        return $this->getAttribute(self::RRN);
    }

    public function getInternalStatus()
    {
        return $this->getAttribute(self::INTERNAL_STATUS);
    }

    public function getReminderId()
    {
        return $this->getAttribute(self::REMINDER_ID);
    }

    public function getRemindAt()
    {
        return $this->getAttribute(self::REMIND_AT);
    }

    public function getApp()
    {
        return $this->getAttribute(self::APP);
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

    public function setMode(string $mode)
    {
        return $this->setAttribute(self::MODE, $mode);
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

    public function setReference($reference)
    {
        return $this->setAttribute(self::REFERENCE, $reference);
    }

    public function setNpciTxnId($npciTxnId)
    {
        return $this->setAttribute(self::NPCI_TXN_ID, $npciTxnId);
    }

    public function setUmn($umn)
    {
        return $this->setAttribute(self::UMN, $umn);
    }

    public function setRrn($rrn)
    {
        return $this->setAttribute(self::RRN, $rrn);
    }

    public function setInternalStatus($internalStatus)
    {
        return $this->setAttribute(self::INTERNAL_STATUS, $internalStatus);
    }

    public function setReminderId($reminderId)
    {
        return $this->setAttribute(self::REMINDER_ID, $reminderId);
    }

    public function setRemindAt($remindAt)
    {
        return $this->setAttribute(self::REMIND_AT, $remindAt);
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

    public function isTypeOtm()
    {
        return ($this->getAttribute(self::TYPE) === Type::OTM);
    }

    public function isFlowCollect()
    {
        return ($this->getAttribute(self::FLOW) === Flow::COLLECT);
    }

    public function isFlowIntent()
    {
        return ($this->getAttribute(self::FLOW) === Flow::INTENT);
    }

    public function isOtmCollect()
    {
        return (($this->isTypeOtm() === true) and ($this->isFlowCollect() === true));
    }

    public function isOtmIntent()
    {
        return (($this->isTypeOtm() === true) and ($this->isFlowIntent() === true));
    }

    public function isInternalStatus(string $status): bool
    {
        return ($this->getInternalStatus() === $status);
    }

    public function canBeAuthorized(): bool
    {
        return $this->isInternalStatus(InternalStatus::AUTHORIZE_INITIATED);
    }

    public function isInAppMode(): bool
    {
        return ($this->getAttribute(self::MODE) === Mode::IN_APP);
    }
}
