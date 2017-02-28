<?php

namespace RZP\Models\Payout;

use RZP\Constants\Table;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Payout;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                = 'id';
    const MERCHANT_ID       = 'merchant_id';
    const CUSTOMER_ID       = 'customer_id';
    const METHOD            = 'method';
    const DESTINATION_ID    = 'destination_id';
    const DESTINATION_TYPE  = 'destination_type';
    const PURPOSE           = 'purpose';
    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const NOTES             = 'notes';
    const FEE               = 'fee';
    const SERVICE_TAX       = 'service_tax';
    const PAYMENT_ID        = 'payment_id';
    const TRANSACTION_ID    = 'transaction_id';
    const STATUS            = 'status';
    const CHANNEL           = 'channel';
    const UTR               = 'utr';
    const FAILURE_REASON    = 'failure_reason';
    const RETURN_UTR        = 'return_utr';
    const REMARKS           = 'remarks';

    // Public attribute
    const DESTINATION       = 'destination';

    protected $entity = 'payout';

    protected $table  = Table::PAYOUT;

    protected $generateIdOnCreate = true;

    protected static $sign = 'pout';

    protected static $generators = [
        self::ID
    ];

    protected $fillable = [
        self::ID,
        self::METHOD,
        self::AMOUNT,
        self::CURRENCY,
        self::STATUS,
        self::NOTES,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::DESTINATION,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::METHOD,
        self::FEE,
        self::SERVICE_TAX,
        self::PAYMENT_ID,
        self::TRANSACTION_ID,
        self::STATUS,
        self::CHANNEL,
        self::UTR,
        self::FAILURE_REASON,
        self::REMARKS,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::DESTINATION,
        self::METHOD,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::FEE,
        self::SERVICE_TAX,
        self::STATUS,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::DESTINATION,
        self::CUSTOMER_ID,
    ];

    protected $defaults = [
        self::STATUS            => Status::CREATED,
        self::PURPOSE           => 'refund',
        self::NOTES             => [],
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEE,
        self::SERVICE_TAX
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::FEE         => 'int',
        self::SERVICE_TAX => 'int',
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function destination()
    {
        return $this->morphTo();
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function transaction()
    {
        return $this->belongsTo('RZP\Models\Transaction\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getServiceTax()
    {
        return $this->getAttribute(self::SERVICE_TAX);
    }

    public function getMethod()
    {
        return $this->getAttribute(self::METHOD);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function isStatusCreated()
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    public function isStatusInitiated()
    {
        return ($this->getStatus() === Status::INITIATED);
    }

    public function isPendingReconciliation()
    {
        return $this->isStatusInitiated();
    }

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getDestinationId()
    {
        return $this->getAttribute(self::DESTINATION_ID);
    }

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setServiceTax($serviceTax)
    {
        $this->setAttribute(self::SERVICE_TAX, $serviceTax);
    }

    public function setFee($fee)
    {
        $this->setAttribute(self::FEE, $fee);
    }

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setRemarks(string $remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setPublicDestinationAttribute(array & $attributes)
    {
        $type = $this->getAttribute(self::DESTINATION_TYPE);

        $entity = Constants\Entity::getEntityClass($type);

        $id = $this->getDestinationId();

        $attributes[self::DESTINATION] = $entity::getSignedId($id);
    }

    public function setPublicCustomerIdAttribute(array & $attributes)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $attributes[self::CUSTOMER_ID] = Customer\Entity::getSignedId($customerId);
    }

    public function getPricingFeatures()
    {
        return [];
    }
}
