<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Base;
use RZP\Constants\Table;
use RZP\Constants;

class Entity extends Base\Entity
{
    const ID                    = 'id';
    const NAME                  = 'name';
    const CITY                  = 'city';
    const MODE                  = 'mode';
    const STATE                 = 'state';
    const PHONE                 = 'phone';
    const EMAIL                 = 'email';
    const STATUS                = 'status';
    const ACTION                = 'action';
    const AMOUNT                = 'amount';
    const CHANNEL               = 'channel';
    const COUNTRY               = 'country';
    const ADDRESS               = 'address';
    const CURRENCY              = 'currency';
    const RECEIVED              = 'received';
    const REFUND_ID             = 'refund_id';
    const REQUEST_ID            = 'request_id';
    const REF_AMOUNT            = 'ref_amount';
    const PAYMENT_ID            = 'payment_id';
    const ACCOUNT_ID            = 'account_id';
    const ERROR_CODE            = 'error_code';
    const POSTAL_CODE           = 'postal_code';
    const DESCRIPTION           = 'description';
    const PAYMENT_MODE          = 'payment_mode';
    const REFUND_REF_NO         = 'reference_no';
    const EBS_PAYMENT_ID        = 'ebs_payment_id';
    const TRANSACTION_ID        = 'transaction_id';
    const ERROR_DESCRIPTION     = 'error_description';

    protected $fields = array(
        self::NAME,
        self::CITY,
        self::MODE,
        self::STATE,
        self::PHONE,
        self::EMAIL,
        self::STATUS,
        self::CHANNEL,
        self::ADDRESS,
        self::COUNTRY,
        self::RECEIVED,
        self::CURRENCY,
        self::ERROR_CODE,
        self::REF_AMOUNT,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::ACCOUNT_ID,
        self::POSTAL_CODE,
        self::DESCRIPTION,
        self::PAYMENT_MODE,
        self::TRANSACTION_ID,
        self::EBS_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
    );

    protected $fillable = array(
        self::NAME,
        self::CITY,
        self::MODE,
        self::STATE,
        self::PHONE,
        self::EMAIL,
        self::STATUS,
        self::CHANNEL,
        self::ADDRESS,
        self::COUNTRY,
        self::RECEIVED,
        self::CURRENCY,
        self::ERROR_CODE,
        self::REF_AMOUNT,
        self::REQUEST_ID,
        self::PAYMENT_ID,
        self::ACCOUNT_ID,
        self::POSTAL_CODE,
        self::DESCRIPTION,
        self::PAYMENT_MODE,
        self::TRANSACTION_ID,
        self::EBS_PAYMENT_ID,
        self::ERROR_DESCRIPTION,
    );

    protected $table = TABLE::EBS;

    protected $entity = Constants\ENTITY::EBS;

}
