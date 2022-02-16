<?php

namespace RZP\Models\P2p\Mandate;

use RZP\Models\P2p\Base;

/**
 * Class Entity
 *
 * @package RZP\Models\P2p\Mandate
 */
class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;
    use Base\Traits\HasMerchant;
    use Base\Traits\HasBankAccount;

    const NAME			            = 'name';
    const DEVICE_ID			        = 'device_id';
    const MERCHANT_ID 		        = 'merchant_id';
    const CUSTOMER_ID 		        = 'customer_id';
    const HANDLE 		            = 'handle';
    const AMOUNT			        = 'amount';
    const AMOUNT_RULE 		        = 'amount_rule';
    const PAYER_ID 			        = 'payer_id';
    const PAYEE_ID 			        = 'payee_id';
    const TYPE 			            = 'type';
    const FLOW 			            = 'flow';
    const MODE 			            = 'mode';
    const RECURRING_TYPE 	        = 'recurring_type';
    const RECURRING_VALUE	        = 'recurring_value';
    const RECURRING_RULE 	        = 'recurring_rule';
    const UMN			            = 'umn';
    const STATUS			        = 'status';
    const INTERNAL_STATUS	        = 'internal_status';
    const START_DATE		        = 'start_date';
    const END_DATE			        = 'end_date';
    const ACTION			        = 'action';
    const DESCRIPTION			    = 'description';
    const GATEWAY                   = 'gateway';
    const INTERNAL_ERROR_CODE       = 'internal_error_code';
    const ERROR_CODE			    = 'error_code';
    const ERROR_DESCRIPTION			= 'error_description';
    const COMPLETED_AT			    = 'completed_at';
    const EXPIRE_AT			        = 'expire_at';

    /************** Input  Properties ************/

    const MANDATE               = 'mandate';
    const CUSTOMER              = 'customer';
    const PAYER                 = 'payer';
    const PAYEE                 = 'payee';
    const UPI                   = 'upi';
    const IS_PENDING_COLLECT    = 'is_pending_collect';

    /************** Entity Properties ************/

    protected $entity       = 'p2p_mandate';
    protected static $sign  = 'cmdt';

    protected $dates = [
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::EXPIRE_AT,
        Entity::CREATED_AT,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::EXPIRE_AT,
        Entity::ACTION,
        Entity::UMN,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::DESCRIPTION,
        Entity::GATEWAY,
        Entity::GATEWAY_DATA,
        Entity::IS_PENDING_COLLECT,
        Entity::COMPLETED_AT,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::MERCHANT_ID,
        Entity::CUSTOMER_ID,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::GATEWAY,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::CUSTOMER,
        Entity::PAYER,
        Entity::PAYEE,
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::RECURRING_TYPE,
        Entity::RECURRING_VALUE,
        Entity::RECURRING_RULE,
        Entity::UMN,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::EXPIRE_AT,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::ACTION,
        Entity::DESCRIPTION,
        Entity::GATEWAY_DATA,
        Entity::IS_PENDING_COLLECT,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
        Entity::COMPLETED_AT,
        Entity::EXPIRE_AT,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::ID,
        Entity::AMOUNT,
        Entity::AMOUNT_RULE,
        Entity::PAYER,
        Entity::PAYEE,
        Entity::CUSTOMER,
        Entity::TYPE,
        Entity::FLOW,
        Entity::RECURRING_TYPE,
        Entity::RECURRING_VALUE,
        Entity::RECURRING_RULE,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::DESCRIPTION,
        Entity::IS_PENDING_COLLECT,
        Entity::ERROR_CODE,
        Entity::ERROR_DESCRIPTION,
        Entity::INTERNAL_ERROR_CODE,
    ];
}
