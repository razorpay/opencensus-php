<?php

namespace RZP\Models\Payout;

use Carbon\Carbon;
use RZP\Constants\Timezone;

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

    const ID                     = 'id';
    const MERCHANT_ID            = 'merchant_id';
    const CUSTOMER_ID            = 'customer_id';
    const METHOD                 = 'method';
    const DESTINATION_ID         = 'destination_id';
    const DESTINATION_TYPE       = 'destination_type';
    const PURPOSE                = 'purpose';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const NOTES                  = 'notes';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const PAYMENT_ID             = 'payment_id';
    const TRANSACTION_ID         = 'transaction_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const STATUS                 = 'status';
    const CHANNEL                = 'channel';
    const UTR                    = 'utr';
    const FAILURE_REASON         = 'failure_reason';
    const RETURN_UTR             = 'return_utr';
    const REMARKS                = 'remarks';
    const PROCESSED_AT           = 'processed_at';
    const SETTLED_ON             = 'settled_on';

    // Public attribute
    const DESTINATION            = 'destination';

    // These are used while creating merchant payouts.
    // Min amount refers to the minimum amount payout has to be
    // Modulo refers to the multiples in which amount should be
    const MIN_AMOUNT             = 'min_amount';
    const MODULO                 = 'modulo';

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
        self::PROCESSED_AT,
        self::SETTLED_ON,
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
        self::FEES,
        self::TAX,
        self::PAYMENT_ID,
        self::TRANSACTION_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::STATUS,
        self::CHANNEL,
        self::UTR,
        self::FAILURE_REASON,
        self::REMARKS,
        self::PROCESSED_AT,
        self::SETTLED_ON,
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
        self::FEES,
        self::TAX,
        self::STATUS,
        self::UTR,
        self::SETTLED_ON,
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
        self::FEES,
        self::TAX,
    ];

    protected $casts = [
        self::AMOUNT      => 'int',
        self::FEES        => 'int',
        self::TAX         => 'int',
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::PROCESSED_AT,
        self::SETTLED_ON,
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

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
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

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getProcessedAt()
    {
        return $this->getAttribute(self::PROCESSED_AT);
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

    public function setTax($tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFees($fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setMethod($method)
    {
        $this->setAttribute(self::METHOD, $method);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setUtr(string $utr = null)
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

    public function setProcessedAt($date)
    {
        $this->setAttribute(self::PROCESSED_AT, $date);
    }

    public function setSettledOn($date)
    {
        $this->setAttribute(self::SETTLED_ON, $date);
    }

    protected function getSettledOnAttribute()
    {
        $timestamp = $this->attributes[self::SETTLED_ON];

        if ($timestamp !== null)
        {
            return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
        }

        return null;
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
