<?php

namespace RZP\Models\P2p\Mandate\UpiMandate;

use RZP\Models\P2p\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\P2p\Mandate
 */
class Validator extends Base\Validator
{
    /**
     * Common rules for Mandate entity attributes
     *
     * @return array|string[]
     */
    public function rules()
    {
        $rules = [
            Entity::DEVICE_ID                    => 'string',
            Entity::ACTION                       => 'string',
            Entity::STATUS                       => 'string',
            Entity::NETWORK_TRANSACTION_ID       => 'string',
            Entity::GATEWAY_TRANSACTION_ID       => 'string',
            Entity::GATEWAY_REFERENCE_ID         => 'string',
            Entity::RRN                          => 'string',
            Entity::REF_ID                       => 'string|max:50',
            Entity::REF_URL                      => 'string|max:255',
            Entity::MCC                          => 'string|size:4',
            Entity::GATEWAY_ERROR_CODE           => 'string',
            Entity::GATEWAY_ERROR_DESCRIPTION    => 'string',
            Entity::RISK_SCORES                  => 'string',
            Entity::PAYER_ACCOUNT_NUMBER         => 'string',
            Entity::PAYER_IFSC_CODE              => 'string',
            Entity::GATEWAY_DATA                 => 'array',
        ];

        return $rules;
    }
}
