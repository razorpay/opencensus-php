<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Constants\Entity as Constants;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID                   = 'id';
    const MERCHANT_ID          = 'merchant_id';
    const STATUS               = 'status';
    const NAME                 = 'name';
    const DESCRIPTOR           = 'descriptor';
    const AMOUNT_EXPECTED      = 'amount_expected';
    const AMOUNT_RECEIVED      = 'amount_received';
    const AMOUNT_PAID          = 'amount_paid';
    const AMOUNT_REVERSED      = 'amount_reversed';
    const BANK_ACCOUNT_ID      = 'bank_account_id';
    const VPA                  = 'vpa';
    const CUSTOMER_ID          = 'customer_id';

    const RECEIVER_TYPE        = 'receiver_type';
    const BANK_ACCOUNT         = 'bank_account';

    const DELETED_AT           = 'deleted_at';

    protected $fillable = [
        self::NAME,
        self::STATUS,
        self::DESCRIPTOR,
        self::AMOUNT_EXPECTED,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ENTITY,
        self::DESCRIPTOR,
        self::STATUS,
        self::AMOUNT_EXPECTED,
        self::AMOUNT_PAID,
        self::CUSTOMER_ID,
        self::BANK_ACCOUNT,
        self::RECEIVER_TYPE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::RECEIVER_TYPE,
    ];

    protected $casts = [
        self::AMOUNT_EXPECTED      => 'int',
        self::AMOUNT_RECEIVED      => 'int',
        self::AMOUNT_PAID          => 'int',
        self::AMOUNT_REVERSED      => 'int',
    ];

    protected $defaults = [
        self::STATUS               => Status::ACTIVE,
        self::AMOUNT_RECEIVED      => 0,
        self::AMOUNT_PAID          => 0,
        self::AMOUNT_REVERSED      => 0,
    ];

    protected static $modifiers = [
        self::NAME,
    ];

    protected static $sign = 'va';

    protected $generateIdOnCreate = true;

    protected $entity = Constants::VIRTUAL_ACCOUNT;

    // ----------------------- Associations ------------------------------------

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', 'bank_account_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------- Modifiers ---------------------------------------

    public function modifyName(& $input)
    {
        if (isset($input[self::NAME]) === false)
        {
            $input[self::NAME] = $this->merchant->getBillingLabel();
        }
    }

    // ----------------------- Checks ------------------------------------------

    public function hasAmountExpected()
    {
        return ($this->isAttributeNotNull(self::AMOUNT_EXPECTED));
    }

    public function hasBankAccount()
    {
        return ($this->isAttributeNotNull(self::BANK_ACCOUNT_ID));
    }

    public function hasVpa()
    {
        return ($this->isAttributeNotNull(self::VPA));
    }

    // ----------------------- Getters -----------------------------------------

    public function getAmountPaid()
    {
        return $this->getAttribute(self::AMOUNT_PAID);
    }

    public function getAmountExpected()
    {
        return $this->getAttribute(self::AMOUNT_EXPECTED);
    }

    public function getAmountReceived()
    {
        return $this->getAttribute(self::AMOUNT_RECEIVED);
    }

    // ----------------------- Setters -----------------------------------------

    public function setStatus(string $status)
    {
        Status::checkStatus($status);

        $this->setAttribute(self::STATUS, $status);
    }

    public function setAmountPaid(int $amount)
    {
        $this->setAttribute(self::AMOUNT_PAID, $amount);
    }

    public function setAmountReceived(int $amount)
    {
        $this->setAttribute(self::AMOUNT_RECEIVED, $amount);
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    protected function setPublicReceiverTypeAttribute(array & $array)
    {
        $receiverTypes = [];

        foreach (Receiver::TYPES as $receiverType)
        {
            $func = 'has' . studly_case($receiverType);

            if ($this->$func() === true)
            {
                $receiverTypes[] = $receiverType;
            }
        }

        $array[self::RECEIVER_TYPE] = $receiverTypes;
    }

    public function incrementAmountPaid(int $amount)
    {
        $amountPaid = $this->getAmountPaid() + $amount;

        $this->setAmountPaid($amountPaid);

        if (($this->hasAmountExpected() === true) and
            ($this->getAmountPaid() >= $this->getAmountExpected()))
        {
            $this->setStatus(Status::PAID);
        }
    }

    public function incrementAmountReceived(int $amount)
    {
        $amountReceived = $this->getAmountReceived() + $amount;

        $this->setAmountReceived($amountReceived);
    }
}
