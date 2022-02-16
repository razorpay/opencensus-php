<?php

namespace RZP\Models\P2p\Mandate\UpiMandate;

use RZP\Models\P2p\Base;

/**
 * Class Entity for upi mandates
 *
 * @package RZP\Models\P2p\Mandate
 */
class Entity extends Base\Entity
{

    const MANDATE_ID			    = 'mandate_id';
    const DEVICE_ID			        = 'device_id';
    const ACTION			        = 'action';
    const STATUS                    = 'status';
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
    const PAYER_ACCOUNT_NUMBER      = 'payer_account_number';
    const PAYER_IFSC_CODE           = 'payer_ifsc_code';
    const GATEWAY_DATA			    = 'gateway_data';

    /************** Entity Properties ************/

    protected $entity       = 'p2p_upi_mandate';
    protected $primaryKey   = Entity::MANDATE_ID;

    protected $dates = [];

    protected $fillable = [
        Entity::MANDATE_ID,
        Entity::DEVICE_ID,
        Entity::ACTION,
        Entity::STATUS,
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
        Entity::PAYER_ACCOUNT_NUMBER,
        Entity::PAYER_IFSC_CODE,
        Entity::GATEWAY_DATA,
    ];

    protected $visible = [
        Entity::MANDATE_ID,
        Entity::DEVICE_ID,
        Entity::ACTION,
        Entity::STATUS,
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
        Entity::PAYER_ACCOUNT_NUMBER,
        Entity::PAYER_IFSC_CODE,
        Entity::GATEWAY_DATA,
    ];

    protected $public = [
        Entity::MANDATE_ID,
        Entity::DEVICE_ID,
        Entity::ACTION,
        Entity::STATUS,
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
        Entity::PAYER_ACCOUNT_NUMBER,
        Entity::PAYER_IFSC_CODE,
        Entity::GATEWAY_DATA,
        Entity::UPDATED_AT,
    ];

    protected $defaults = [
        Entity::ACTION                      => null,
        Entity::STATUS                      => null,
        Entity::NETWORK_TRANSACTION_ID      => null,
        Entity::GATEWAY_TRANSACTION_ID      => null,
        Entity::GATEWAY_REFERENCE_ID        => null,
        Entity::RRN                         => null,
        Entity::REF_ID                      => null,
        Entity::GATEWAY_ERROR_CODE          => null,
        Entity::GATEWAY_ERROR_DESCRIPTION   => null,
        Entity::RISK_SCORES                 => null,
        Entity::PAYER_ACCOUNT_NUMBER        => null,
        Entity::PAYER_IFSC_CODE             => null,
        Entity::GATEWAY_DATA                => [],
    ];

    protected $casts = [
        Entity::MANDATE_ID                   => 'string',
        Entity::DEVICE_ID                    => 'string',
        Entity::ACTION                       => 'string',
        Entity::STATUS                       => 'string',
        Entity::NETWORK_TRANSACTION_ID       => 'string',
        Entity::GATEWAY_TRANSACTION_ID       => 'string',
        Entity::GATEWAY_REFERENCE_ID         => 'string',
        Entity::RRN                          => 'string',
        Entity::REF_ID                       => 'string',
        Entity::GATEWAY_ERROR_CODE           => 'string',
        Entity::GATEWAY_ERROR_DESCRIPTION    => 'string',
        Entity::RISK_SCORES                  => 'string',
        Entity::PAYER_ACCOUNT_NUMBER         => 'string',
        Entity::PAYER_IFSC_CODE              => 'string',
        Entity::GATEWAY_DATA                 => 'array',
    ];
}
