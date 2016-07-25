<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;
use RZP\Constants\Table;
use RZP\Constants;

class Entity extends Base\Entity
{
    const PAYMENT_ID            = 'payment_id';
    const CHANNEL               = 'channel';
    const RECEIVED              = 'received';
    const ACCOUNT_ID            = 'account_id';
    const TXN_AMOUNT            = 'TxnAmount';
    const AMOUNT                = 'amount';
    const NAME                  = 'name';
    const ADDRESS               = 'address';
    const CITY                  = 'city';
    const STATE                 = 'state';
    const COUNTRY               = 'country';
    const POSTAL_CODE           = 'postal_code';
    const PHONE                 = 'phone';
    const EMAIL                 = 'email';
    const DESCRIPTION           = 'description';
    const CURRENCY              = 'currency';
    const MODE                  = 'mode';
    const PAYMENT_MODE          = 'payment_mode';
    const REQUEST_ID            = 'Request_id';
    const TRANSACTION_ID        = 'transaction_id';
    const REF_AMOUNT            = 'ref_amount';
    const EBS_PAYMENT_ID        = 'ebs_payment_id';
    const ERROR_DESCRIPTION     = 'error_description';
    const ERROR_CODE            = 'error_code';
    const REFUND_ID             = 'refund_id';
    const REFUND_REF_NO         = 'reference_no';
    const REFUND_PAYMENT_ID     = 'payment_id';

    protected $fields = array(
        self::PAYMENT_ID,
        self::CHANNEL,
        self::RECEIVED,
        self::ACCOUNT_ID,
        self::TXN_AMOUNT,
        self::NAME,
        self::ADDRESS,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::POSTAL_CODE,
        self::PHONE,
        self::EMAIL,
        self::DESCRIPTION,
        self::CURRENCY,
        self::MODE,
        self::PAYMENT_MODE,
        self::REQUEST_ID,
        self::TRANSACTION_ID,
        self::TXN_AMOUNT,
        self::REF_AMOUNT,
        self::EBS_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
        self::ERROR_CODE
    );

    protected $fillable = array(
        self::PAYMENT_ID,
        self::CHANNEL,
        self::RECEIVED,
        self::ACCOUNT_ID,
        self::TXN_AMOUNT,
        self::NAME,
        self::ADDRESS,
        self::CITY,
        self::STATE,
        self::COUNTRY,
        self::POSTAL_CODE,
        self::PHONE,
        self::EMAIL,
        self::DESCRIPTION,
        self::CURRENCY,
        self::MODE,
        self::PAYMENT_MODE,
        self::REQUEST_ID,
        self::TRANSACTION_ID,
        self::TXN_AMOUNT,
        self::REF_AMOUNT,
        self::EBS_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
        self::ERROR_CODE
    );

    protected $table = TABLE::EBS;

    protected $entity = Constants\ENTITY::EBS;

    protected $appends = array('status', 'refund_status');

    protected function getStatusAttribute()
    {
        $code = $this->attributes['AuthStatus'];

        if ($code === null)
        {
            return null;
        }

        return AuthStatus::$statusMap[$code];
    }

    protected function getRefundStatusAttribute()
    {
        $code = $this->attributes['RefStatus'];

        if ($code === null)
        {
            return null;
        }

        return RefundStatus::$statusMap[$code];
    }
}
