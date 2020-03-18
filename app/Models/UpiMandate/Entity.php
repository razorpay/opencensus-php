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
    const GATEWAY_REFERENCE_ID = 'gateway_reference_id';

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
        self::GATEWAY_REFERENCE_ID,
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
        self::GATEWAY_REFERENCE_ID,
    ];

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
}
