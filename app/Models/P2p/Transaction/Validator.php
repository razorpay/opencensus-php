<?php

namespace RZP\Models\P2p\Transaction;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Beneficiary;
use RZP\Models\P2p\Base\Upi\Txn;
use RZP\Models\P2p\BankAccount\Credentials;

class Validator extends Base\Validator
{
    // Incoming pay and collect, we are allowing @ and -, because of other PSPs
    const GLOBAL_DESCRIPTION_REGEX  = '/^[a-zA-Z0-9\.\ \@\-]{1,}$/';
    // We are still not allowing for any initiate call. For intent, it is still blocked.
    const LIMITED_DESCRIPTION_REGEX = '/^[a-zA-Z0-9\.\ ]{1,}$/';

    protected static $initiatePayRules;
    protected static $initiatePaySuccessRules;
    protected static $initiateCollectRules;
    protected static $initiateCollectSuccessRules;
    protected static $initiateAuthorizeRules;
    protected static $initiateAuthorizeSuccessRules;
    protected static $authorizeTransactionRules;
    protected static $authorizeTransactionSuccessRules;
    protected static $initiateRejectRules;
    protected static $rejectRules;
    protected static $rejectSuccessRules;
    protected static $incomingCollectRules;
    protected static $incomingPayRules;
    protected static $raiseConcernRules;
    protected static $raiseConcernSuccessRules;
    protected static $concernStatusRules;
    protected static $concernStatusSuccessRules;

    public function rules()
    {
        $expiryAt = 'min:' . Carbon::now()->addMinute()->getTimestamp() .
                    'max:' . Carbon::now()->addDays(45)->getTimestamp();

        $modes    = 'in:' . implode(',', Mode::$allowed);

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
            Entity::MODE                 => 'string|' . $modes,
            Entity::AMOUNT               => 'integer|min:1|max:10000000',
            Entity::CURRENCY             => 'string|in:INR',
            Entity::DESCRIPTION          => 'string|regex:' . self::GLOBAL_DESCRIPTION_REGEX,
            Entity::GATEWAY              => 'string',
            Entity::STATUS               => 'string',
            Entity::INTERNAL_STATUS      => 'string',
            Entity::ERROR_CODE           => 'string',
            Entity::ERROR_DESCRIPTION    => 'string',
            Entity::INTERNAL_ERROR_CODE  => 'string',
            Entity::PAYER_APPROVAL_CODE  => 'string',
            Entity::PAYEE_APPROVAL_CODE  => 'string',
            Entity::INITIATED_AT         => 'epoch',
            Entity::EXPIRE_AT            => 'epoch|' . $expiryAt,
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
            Entity::INTERNAL_ERROR_CODE  => 'sometimes',
            Entity::EXPIRE_AT            => 'sometimes',
        ]);

        return $rules;
    }

    public function makeInitiatePayRules(bool $global = false)
    {
        $regex = $global ? self::GLOBAL_DESCRIPTION_REGEX : self::LIMITED_DESCRIPTION_REGEX;

        $rules = $this->makeRules([
            Entity::PAYER                => 'required',
            Entity::PAYEE                => 'required',
            Entity::AMOUNT               => 'required',
            Entity::CURRENCY             => 'required',
            Entity::DESCRIPTION          => 'sometimes|regex:' . $regex,
            Entity::MODE                 => 'sometimes',
        ]);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeInitiatePaySuccessRules()
    {
        $rules = $this->makeEntityIdRules()->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeInitiateCollectRules(bool $global = false)
    {
        $regex = $global ? self::GLOBAL_DESCRIPTION_REGEX : self::LIMITED_DESCRIPTION_REGEX;

        $rules = $this->makeRules([
            Entity::PAYER                => 'required',
            Entity::PAYEE                => 'required',
            Entity::AMOUNT               => 'required',
            Entity::CURRENCY             => 'required',
            Entity::DESCRIPTION          => 'sometimes|regex:' . $regex,
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

    public function makeInitiateRejectRules()
    {
        $rules = $this->makePublicIdRules();

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

    public function makeIncomingCollectRules()
    {
        $rules = $this->makeInitiateCollectRules(true)->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeIncomingPayRules()
    {
        $rules = $this->makeInitiatePayRules(true)->wrapRules(Entity::TRANSACTION);

        $rules->merge($this->makeUpiRules());

        return $rules;
    }

    public function makeRaiseConcernRules()
    {
        $concernRules = (new Concern\Validator)->makeRules([
            Concern\Entity::COMMENT => 'required',
        ]);

        $rules = $this->makePublicIdRules();

        $rules->merge($concernRules);

        return $rules;
    }

    public function makeRaiseConcernSuccessRules()
    {
        $rules = (new Concern\Validator)->makeRules([
            Concern\Entity::ID                      => 'required',
            Concern\Entity::GATEWAY_REFERENCE_ID    => 'required',
            Concern\Entity::INTERNAL_STATUS         => 'required',
            Concern\Entity::RESPONSE_CODE           => 'required',
            Concern\Entity::HANDLE                  => 'sometimes',
            Concern\Entity::TRANSACTION_ID          => 'sometimes',
            Concern\Entity::RESPONSE_DESCRIPTION    => 'sometimes',
            Concern\Entity::GATEWAY_DATA            => 'sometimes',
        ])->wrapRules(Entity::CONCERN);

        return $rules;
    }

    public function makeConcernStatusRules()
    {
        return $this->makePublicIdRules();
    }
}
