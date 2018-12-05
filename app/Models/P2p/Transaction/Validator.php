<?php

namespace RZP\Models\P2p\Transaction;

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
            Entity::MERCHANT_ID          => 'string',
            Entity::CUSTOMER_ID          => 'string',
            Entity::PAYER_TYPE           => 'string',
            Entity::PAYER_ID             => 'string',
            Entity::PAYEE_TYPE           => 'string',
            Entity::PAYEE_ID             => 'string',
            Entity::BANK_ACCOUNT_ID      => 'string',
            Entity::METHOD               => 'string',
            Entity::TYPE                 => 'string',
            Entity::FLOW                 => 'string',
            Entity::MODE                 => 'string',
            Entity::AMOUNT               => 'string',
            Entity::CURRENCY             => 'string',
            Entity::DESCRIPTION          => 'string',
            Entity::GATEWAY              => 'string',
            Entity::STATUS               => 'string',
            Entity::INTERNAL_STATUS      => 'string',
            Entity::ERROR_CODE           => 'string',
            Entity::ERROR_DESCRIPTION    => 'string',
            Entity::INTERNAL_ERROR_CODE  => 'string',
            Entity::PAYER_APPROVAL_CODE  => 'string',
            Entity::PAYEE_APPROVAL_CODE  => 'string',
            Entity::INITIATED_AT         => 'string',
            Entity::EXPIRE_AT            => 'string',
            Entity::COMPLETED_AT         => 'string',
        ];

        return $rules;
    }

    protected function getCreateRules()
    {
        $rules = $this->makeRules([
            Entity::MERCHANT_ID          => 'sometimes',
            Entity::CUSTOMER_ID          => 'sometimes',
            Entity::PAYER_TYPE           => 'sometimes',
            Entity::PAYER_ID             => 'sometimes',
            Entity::PAYEE_TYPE           => 'sometimes',
            Entity::PAYEE_ID             => 'sometimes',
            Entity::BANK_ACCOUNT_ID      => 'sometimes',
            Entity::METHOD               => 'sometimes',
            Entity::TYPE                 => 'sometimes',
            Entity::FLOW                 => 'sometimes',
            Entity::MODE                 => 'sometimes',
            Entity::AMOUNT               => 'sometimes',
            Entity::CURRENCY             => 'sometimes',
            Entity::DESCRIPTION          => 'sometimes',
            Entity::GATEWAY              => 'sometimes',
            Entity::STATUS               => 'sometimes',
            Entity::INTERNAL_STATUS      => 'sometimes',
            Entity::ERROR_CODE           => 'sometimes',
            Entity::ERROR_DESCRIPTION    => 'sometimes',
            Entity::INTERNAL_ERROR_CODE  => 'sometimes',
            Entity::PAYER_APPROVAL_CODE  => 'sometimes',
            Entity::PAYEE_APPROVAL_CODE  => 'sometimes',
            Entity::INITIATED_AT         => 'sometimes',
            Entity::EXPIRE_AT            => 'sometimes',
            Entity::COMPLETED_AT         => 'sometimes',
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
