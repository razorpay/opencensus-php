<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $initiatePayRules;
    protected static $initiateCollectRules;
    protected static $fetchAllRules;
    protected static $fetchRules;
    protected static $initiateAuthorizeRules;
    protected static $authorizeRules;
    protected static $rejectRules;

    protected function rules()
    {
        $rules = [
            Entity::TRANSACTION_ID               => 'string',
            Entity::DEVICE_ID                    => 'string',
            Entity::HANDLE                       => 'string',
            Entity::GATEWAY_DATA                 => 'array',
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
            Entity::PAYEE_ACCOUNT_NUMBER         => 'string',
            Entity::PAYEE_IFSC_CODE              => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::TRANSACTION_ID               => 'sometimes',
            Entity::DEVICE_ID                    => 'sometimes',
            Entity::HANDLE                       => 'sometimes',
            Entity::GATEWAY_DATA                 => 'sometimes',
            Entity::ACTION                       => 'sometimes',
            Entity::STATUS                       => 'sometimes',
            Entity::NETWORK_TRANSACTION_ID       => 'sometimes',
            Entity::GATEWAY_TRANSACTION_ID       => 'sometimes',
            Entity::GATEWAY_REFERENCE_ID         => 'sometimes',
            Entity::RRN                          => 'sometimes',
            Entity::REF_ID                       => 'sometimes',
            Entity::GATEWAY_ERROR_CODE           => 'sometimes',
            Entity::GATEWAY_ERROR_DESCRIPTION    => 'sometimes',
            Entity::RISK_SCORES                  => 'sometimes',
            Entity::PAYER_ACCOUNT_NUMBER         => 'sometimes',
            Entity::PAYER_IFSC_CODE              => 'sometimes',
            Entity::PAYEE_ACCOUNT_NUMBER         => 'sometimes',
            Entity::PAYEE_IFSC_CODE              => 'sometimes',
        ]);

        return $rules;
    }

    protected function getInitiatePayRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getInitiateCollectRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchAllRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getFetchRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getInitiateAuthorizeRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getAuthorizeRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    protected function getRejectRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }
}
