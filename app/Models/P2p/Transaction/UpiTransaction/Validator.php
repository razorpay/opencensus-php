<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Validator extends Base\Validator
{
    protected static $editRules;
    protected static $initiatePayRules;
    protected static $initiateCollectRules;
    protected static $fetchAllRules;
    protected static $fetchRules;
    protected static $initiateAuthorizeRules;
    protected static $authorizeRules;
    protected static $rejectRules;
    protected static $incomingCollectRules;

    public function rules()
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
            Entity::REF_ID                       => 'string|max:50',
            Entity::REF_URL                      => 'string|max:255',
            Entity::MCC                          => 'string|size:4',
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

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::TRANSACTION_ID               => 'sometimes',
            Entity::GATEWAY_DATA                 => 'sometimes',
            Entity::ACTION                       => 'sometimes',
            Entity::STATUS                       => 'sometimes',
            Entity::NETWORK_TRANSACTION_ID       => 'sometimes',
            Entity::GATEWAY_TRANSACTION_ID       => 'sometimes',
            Entity::GATEWAY_REFERENCE_ID         => 'sometimes',
            Entity::RRN                          => 'sometimes',
            Entity::REF_ID                       => 'sometimes',
            Entity::REF_URL                      => 'sometimes',
            Entity::MCC                          => 'sometimes',
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

    public function makeEditRules()
    {
        $rules = $this->makeRules([
            Entity::GATEWAY_DATA                 => 'sometimes',
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
            Entity::REF_URL                      => 'sometimes',
            Entity::MCC                          => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiatePayRules()
    {
        return $this->makeRules([
            Entity::REF_ID                       => 'sometimes',
            Entity::REF_URL                      => 'sometimes',
            Entity::MCC                          => 'sometimes',
        ]);
    }

    public function makeInitiatePaySuccessRules()
    {
        $rules = $this->makeRules([
            Entity::TRANSACTION_ID          => 'required',
            Entity::NETWORK_TRANSACTION_ID  => 'required',
            Entity::GATEWAY_TRANSACTION_ID  => 'required',
        ]);

        return $rules;
    }

    public function makeInitiateCollectSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::NETWORK_TRANSACTION_ID  => 'required',
            Entity::GATEWAY_TRANSACTION_ID  => 'required',
            Entity::GATEWAY_REFERENCE_ID    => 'sometimes',
            Entity::RRN                     => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiateAuthorizeSuccessRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeAuthorizeTransactionSuccessRules()
    {
        $rules = $this->makeRules([
            Entity::NETWORK_TRANSACTION_ID  => 'required',
            Entity::GATEWAY_TRANSACTION_ID  => 'required',
            Entity::GATEWAY_REFERENCE_ID    => 'required',
            Entity::RRN                     => 'required',
        ]);

        return $rules;
    }

    public function makeRejectSuccessRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeIncomingCollectRules()
    {
        return $this->makeAuthorizeTransactionSuccessRules();
    }

    public function makeIncomingPayRules()
    {
        return $this->makeAuthorizeTransactionSuccessRules();
    }
}
