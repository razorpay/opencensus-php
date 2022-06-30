<?php

namespace RZP\Models\OfflinePayment;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transaction;
use RZP\Models\VirtualAccount;

/**
 * @property Payment\Entity        $payment
 * @property Merchant\Entity       $merchant
 * @property VirtualAccount\Entity $virtualAccount
 * @property Balance\Entity        $balance
 */
class Entity extends Base\PublicEntity
{
    //PID is payer_instrument_details , PD is payer_details
    const ID                           = 'id';
    const CHALLAN_NUMBER               = 'challan_number';
    const AMOUNT                       = 'amount';
    const MODE                         = 'mode';
    const STATUS                       = 'status';
    const DESCRIPTION                  = 'description';
    const BANK_REFERENCE_NUMBER        = 'bank_reference_number';
    const PID_REFERENCE_NUMBER         = 'pid_reference_number';
    const PID_MICR_CODE                = 'pid_micr_code';
    const PID_DATE                     = 'pid_date';
    const PD_NAME                      = 'pd_name';
    const PD_IFSC                      = 'pd_ifsc';
    const PD_BRANCH_CITY               = 'pd_branch_city';
    const PAYMENT_TIMESTAMP            = 'payment_timestamp';
    const ADDITIONAL_INFO              = 'additional_info';
    const CLIENT_CODE                  = 'client_code';
    const VIRTUAL_ACCOUNT_ID           = 'virtual_account_id';
    const PAYMENT_INSTRUMENT_DETAILS   = 'payment_instrument_details';
    const PAYER_DETAILS                = 'payer_details';

    const PAYMENT_ID         = 'payment_id';


    const BALANCE_ID         = 'balance_id';
    const VIRTUAL_ACCOUNT    = 'virtual_account';

    const TRANSACTION_ID     = 'transaction_id';
    // Currency of payment in notification
    const CURRENCY           = 'currency';
    const GATEWAY            = 'gateway';

    const CHALLAN_LENGTH = 16;

    // Indicates whether the bank transfer corresponds
    // to an active virtual account on our side. If
    // false, this transfer will need to be refunded
    const EXPECTED          = 'expected';
    const UNEXPECTED_REASON = 'unexpected_reason';
    const ERROR             = 'error';
    const SUCCESS           = 'success';
    const AUTH              = 'auth';



    protected $fillable = [
        self::CHALLAN_NUMBER,
        self::MODE,
        self::AMOUNT,
        self::DESCRIPTION,
        self::STATUS,
        self::CLIENT_CODE,
        self::VIRTUAL_ACCOUNT_ID,
        self::MERCHANT_ID,
        self::PAYMENT_TIMESTAMP,
        self::PAYMENT_INSTRUMENT_DETAILS,
        self::PAYER_DETAILS
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::MODE,
        self::AMOUNT,
        self::VIRTUAL_ACCOUNT_ID,
        self::CLIENT_CODE,
        self::CHALLAN_NUMBER,
        self::PAYMENT_TIMESTAMP,
        self::PAYMENT_INSTRUMENT_DETAILS,
        self::PAYER_DETAILS
    ];


    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::BALANCE_ID,
        self::AMOUNT,
        self::DESCRIPTION,
        self::MODE,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STATUS,
        self::CLIENT_CODE,
        self::PAYMENT_TIMESTAMP,
        self::PAYMENT_INSTRUMENT_DETAILS,
        self::PAYER_DETAILS
    ];



    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
        self::MODE,
    ];


    protected $entity = Constants\Entity::OFFLINE_PAYMENT;

    protected $generateIdOnCreate = true;


    // ----------------------- Associations ------------------------------------

    public function payment()
    {
        return $this->belongsTo('RZP\Models\Payment\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function virtualAccount()
    {
        return $this->belongsTo('RZP\Models\VirtualAccount\Entity');
    }

    public function balance()
    {
        return $this->belongsTo('RZP\Models\Balance\Entity');
    }

    /**
     * For business banking there would be a transaction created per transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function transaction()
    {
        return $this->morphOne(Transaction\Entity::class, 'source', 'type', 'entity_id');
    }

    // ----------------------- Public Setters ----------------------------------

    public function setPublicVirtualAccountIdAttribute(array & $array)
    {
        if (isset($array[self::VIRTUAL_ACCOUNT_ID]) === true)
        {
            $virtualAccountId = $array[self::VIRTUAL_ACCOUNT_ID];

            $array[self::VIRTUAL_ACCOUNT_ID] = VirtualAccount\Entity::getSignedId($virtualAccountId);
        }
    }




    // -------------------------- Mutators -------------------------------------

    public function setAmountAttribute(float $amount)
    {
        //
        // If you're wondering why this is here, run "(int) (579.3 * 100)" in tinker
        //
        // The value of (579.3 * 100) is actually stored as 57929.999... and casting
        // that to an integer just dumps the decimal part and ruins everything.
        //
        // testBankTransferFloatingPointImprecision exists to check against this.
        //

        $amount = (int) number_format(($amount), 0, '.', '');

        $this->attributes[self::AMOUNT] = $amount;
    }




    // -------------------------- Getters --------------------------------------

    public function getMethod()
    {
        return Payment\Method::OFFLINE;
    }

    public function getVirtualAccountId()
    {
        return $this->getAttribute(self::VIRTUAL_ACCOUNT_ID);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getClientCode()
    {
        return $this->getAttribute(self::CLIENT_CODE);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getChallanNumber()
    {
        return $this->getAttribute(self::CHALLAN_NUMBER);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getAuth()
    {
        return $this->getAttribute(self::AUTH);
    }


    public function toArrayTrace(): array
    {
        return $this->toArray();

    }

    public function getUnexpectedReason()
    {
        return $this->getAttribute(self::UNEXPECTED_REASON);
    }

    // ----------------------- Setters -----------------------------------------

    public function setAuth($auth)
    {
        $this->setAttribute(self::AUTH, $auth);
    }

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    public function setUnexpectedReason(string $unexpectedReason)
    {
        $this->setAttribute(self::UNEXPECTED_REASON, $unexpectedReason);
    }


}
