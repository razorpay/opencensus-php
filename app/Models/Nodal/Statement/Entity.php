<?php

namespace RZP\Models\Nodal\Statement;

use RZP\Constants;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const BANK_NAME                 = 'bank_name';
    const SENDER_ACCOUNT_NUMBER     = 'sender_account_number';
    const RECEIVER_ACCOUNT_NUMBER   = 'receiver_account_number';
    const PARTICULARS               = 'particulars';
    const BANK_REFERENCE_NUMBER     = 'bank_reference_number';
    const DEBIT                     = 'debit';
    const CREDIT                    = 'credit';
    const BALANCE                   = 'balance';
    const TRANSACTION_DATE          = 'transaction_date';
    const PROCESSED_ON              = 'processed_on';
    const MODE                      = 'mode';
    const CMS                       = 'cms';
    const REFERENCE1                = 'reference1';
    const REFERENCE2                = 'reference2';
    const CREATED_AT                = 'created_at';
    const UPDATED_AT                = 'updated_at';
    const HASH                      = 'hash';

    protected $entity  = Constants\Entity::NODAL_STATEMENT;

    protected $visible = [
        self::ID,
        self::BANK_NAME,
        self::PARTICULARS,
        self::BANK_REFERENCE_NUMBER,
        self::DEBIT,
        self::CREDIT,
        self::TRANSACTION_DATE,
        self::PROCESSED_ON,
        self::MODE,
        self::CMS,
        self::REFERENCE1,
        self::REFERENCE2,
        self::CREATED_AT
    ];
}
