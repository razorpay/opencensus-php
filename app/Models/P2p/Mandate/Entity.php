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

    const DEVICE_ID			        = 'device_id';
    const CLIENT_ID			        = 'client_id';
    const CUSTOMER_ID 		        = 'customer_id';
    const AMOUNT			        = 'amount';
    const AMOUNT_RULE 		        = 'amount_rule';
    const GATEWAY                   = 'gateway';
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
    const EXPIRE_AT			        = 'expire_at';
    const START_DATE		        = 'start_date';
    const END_DATE			        = 'end_date';
    const DETAILS			        = 'details';
    const ACTION			        = 'action';
    const ACTIVE			        = 'active';
    const DESCRIPTION			    = 'description';
    const NETWORK_TRANSACTION_ID	= 'network_transaction_id';
    const GATEWAY_TRANSACTION_ID	= 'gateway_transaction_id';
    const GATEWAY_REFERENCE_ID		= 'gateway_reference_id';
    const RRN			            = 'rrn';
    const REF_ID			        = 'ref_id';
    const REF_URL			        = 'ref_url';
    const MCC			            = 'mcc';
    const GATEWAY_ERROR_CODE 	    = 'gateway_error_code';
    const GATEWAY_ERROR_DESCRIPTION	= 'gateway_error_description';
    const RISK_SCORES 			    = 'risk_scores';
    const GATEWAY_DATA			    = 'gateway_data';

    /************** Input  Properties ************/

    const MANDATE               = 'mandate';
    const CUSTOMER              = 'customer';
    const PAYER                 = 'payer';
    const PAYEE                 = 'payee';
    const IS_PENDING_COLLECT    = 'is_pending_collect';

    /************** Entity Properties ************/

    protected $entity       = 'p2p_mandate';
    protected static $sign  = 'cmdt';

    protected $dates = [
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::EXPIRE_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::TYPE,
        Entity::FLOW,
        Entity::MODE,
        Entity::AMOUNT,
        Entity::DESCRIPTION,
        Entity::PAYER_ID,
        Entity::PAYEE_ID,
        Entity::DETAILS,
        Entity::GATEWAY,
        Entity::STATUS,
        Entity::INTERNAL_STATUS,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::EXPIRE_AT,
        Entity::ACTIVE,
        Entity::GATEWAY_DATA,
        Entity::ACTION,
        Entity::STATUS,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::GATEWAY_TRANSACTION_ID,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RRN,
        Entity::UMN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::MCC,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::RISK_SCORES,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::CLIENT_ID,
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
        Entity::DETAILS,
        Entity::ACTION,
        Entity::ACTIVE,
        Entity::DESCRIPTION,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::GATEWAY_TRANSACTION_ID,
        Entity::GATEWAY_REFERENCE_ID,
        Entity::RRN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::MCC,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::RISK_SCORES,
        Entity::GATEWAY_DATA,
        Entity::IS_PENDING_COLLECT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
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
        Entity::UMN,
        Entity::STATUS,
        Entity::EXPIRE_AT,
        Entity::START_DATE,
        Entity::END_DATE,
        Entity::DETAILS,
        Entity::DESCRIPTION,
        Entity::NETWORK_TRANSACTION_ID,
        Entity::RRN,
        Entity::REF_ID,
        Entity::REF_URL,
        Entity::GATEWAY_ERROR_CODE,
        Entity::GATEWAY_ERROR_DESCRIPTION,
        Entity::IS_PENDING_COLLECT,
        Entity::CREATED_AT,
    ];
}
