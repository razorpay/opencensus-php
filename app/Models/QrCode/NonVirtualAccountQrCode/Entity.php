<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\QrCode;
use RZP\Models\Customer;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\P2p\Base\Traits\SoftDeletes;

class Entity extends QrCode\Entity
{
    use SoftDeletes;
    use NotesTrait;

    const NAME                     = 'name';
    const USAGE_TYPE               = 'usage_type';
    const STATUS                   = 'status';
    const DESCRIPTION              = 'description';
    const FIXED_AMOUNT             = 'fixed_amount';
    const PAYMENTS_AMOUNT_RECEIVED = 'payments_amount_received';
    const PAYMENTS_RECEIVED_COUNT  = 'payments_received_count';
    const NOTES                    = 'notes';
    const CUSTOMER_ID              = 'customer_id';
    const CLOSE_BY                 = 'close_by';
    const CLOSED_AT                = 'closed_at';
    const CLOSE_REASON             = 'close_reason';

    protected $fillable = [
        self::PROVIDER,
        self::REFERENCE,
        self::QR_STRING,
        self::NAME,
        self::USAGE_TYPE,
        self::FIXED_AMOUNT,
        self::AMOUNT,
        self::STATUS,
        self::DESCRIPTION,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::PAYMENTS_RECEIVED_COUNT,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON
    ];

    protected $visible = [
        self::ID,
        self::REFERENCE,
        self::QR_STRING,
        self::AMOUNT,
        self::PROVIDER,
        self::SHORT_URL,
        self::CREATED_AT,
        self::FIXED_AMOUNT,
        self::NAME,
        self::USAGE_TYPE,
        self::STATUS,
        self::DESCRIPTION,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::PAYMENTS_RECEIVED_COUNT,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SHORT_URL,
        self::CREATED_AT,
        self::NAME,
        self::USAGE_TYPE,
        self::PROVIDER,
        self::AMOUNT,
        self::STATUS,
        self::DESCRIPTION,
        self::FIXED_AMOUNT,
        self::PAYMENTS_AMOUNT_RECEIVED,
        self::PAYMENTS_RECEIVED_COUNT,
        self::NOTES,
        self::CUSTOMER_ID,
        self::CLOSE_BY,
        self::CLOSED_AT,
        self::CLOSE_REASON
    ];

    protected $casts = [
        self::AMOUNT       => 'int',
        self::FIXED_AMOUNT => 'bool',
    ];

    protected $defaults = [
        self::PAYMENTS_AMOUNT_RECEIVED => 0,
        self::PAYMENTS_RECEIVED_COUNT  => 0,
    ];

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setClosedAt(int $closedAt)
    {
        $this->setAttribute(self::CLOSED_AT, $closedAt);
    }

    public function setCloseReason(string $closeReason)
    {
        CloseReason::checkCloseReason($closeReason);

        $this->setAttribute(self::CLOSE_REASON, $closeReason);
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    public function isFixedAmount()
    {
        $this->getAttribute(self::FIXED_AMOUNT);
    }

    public function getAmount()
    {
        $this->getAttribute(self::AMOUNT);
    }
}
