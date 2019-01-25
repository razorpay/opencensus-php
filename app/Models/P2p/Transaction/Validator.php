<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Upi\Txn;
use RZP\Models\P2p\BankAccount\Credentials;

class Validator extends Base\Validator
{
    protected static $initiatePayRules;
    protected static $initiatePaySuccessRules;
    protected static $initiateCollectRules;
    protected static $initiateCollectSuccessRules;
    protected static $initiateAuthorizeRules;
    protected static $initiateAuthorizeSuccessRules;
    protected static $authorizeTransactionRules;
    protected static $authorizeTransactionSuccessRules;
    protected static $rejectRules;
    protected static $rejectSuccessRules;

    public function rules()
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
            Entity::AMOUNT               => 'integer',
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
            Entity::INITIATED_AT         => 'epoch',
            Entity::EXPIRE_AT            => 'epoch',
            Entity::COMPLETED_AT         => 'epoch',
            Entity::SUCCESS              => 'boolean',
        ];

        return $rules;
    }

    public function makeUpiRules()
    {
        // Just to make sure validator function does not send any other value
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        $upiRules = (new UpiTransaction\Validator)->{$function}();

        return $upiRules->wrapRules(Entity::UPI);
    }

    public function makeCredBlockRules()
    {
        $credRules = Credentials::rules()->with([
            Credentials::TYPE           => 'required',
            Credentials::SUB_TYPE       => 'required',
            Credentials::STRING         => 'required',
            Credentials::CODE           => 'required',
            Credentials::KI             => 'required',
        ]);

        return $credRules->wrapRules(Credentials::CREDS, true)
                         ->wrapRules(Entity::CL);
    }

    public function makeCreateRules()
    {
        $rules = $this->makeRules([
            Entity::METHOD               => 'required',
            Entity::TYPE                 => 'required',
            Entity::FLOW                 => 'required',
            Entity::MODE                 => 'required',
            Entity::AMOUNT               => 'required',
            Entity::CURRENCY             => 'required',
            Entity::DESCRIPTION          => 'required',
            Entity::GATEWAY              => 'required',
            Entity::STATUS               => 'required',
            Entity::INTERNAL_STATUS      => 'required',
            Entity::EXPIRE_AT            => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiatePayRules()
    {
        $rules = $this->makeRules([
            Entity::PAYER_ID             => 'required',
            Entity::PAYEE_ID             => 'required',
            Entity::AMOUNT               => 'required',
            Entity::CURRENCY             => 'required',
            Entity::DESCRIPTION          => 'required',
        ]);

        return $rules;
    }

    public function makeInitiatePaySuccessRules()
    {
        $rules = $this->makeEntityIdRules()->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeInitiateCollectRules()
    {
        $rules = $this->makeRules([
            Entity::PAYER_ID             => 'required',
            Entity::PAYEE_ID             => 'required',
            Entity::AMOUNT               => 'required',
            Entity::CURRENCY             => 'required',
            Entity::DESCRIPTION          => 'required',
            Entity::EXPIRE_AT            => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiateCollectSuccessRules()
    {
        $rules = $this->makeEntityIdRules()->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeInitiateAuthorizeRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeInitiateAuthorizeSuccessRules()
    {
        $rules = $this->makeRules([]);

        return $rules;
    }

    public function makeAuthorizeTransactionRules()
    {
        $rules = $this->makePublicIdRules();

        $rules->merge($this->makeCredBlockRules());

        return $rules;
    }

    public function makeAuthorizeTransactionSuccessRules()
    {
        $rules = $this->makeEntityIdRules()->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeRejectRules()
    {
        $rules = $this->makePublicIdRules();

        return $rules;
    }

    public function makeRejectSuccessRules()
    {
        $rules = $this->makeEntityIdRules()->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeRules([
            Entity::SUCCESS => 'required|in:1',
        ]));

        return $rules;
    }
}
