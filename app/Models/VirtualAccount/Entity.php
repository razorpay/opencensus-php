<?php

namespace RZP\Models\VirtualAccount;

use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Merchant;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as Constants;
use RZP\Models\Base\Traits\NotesTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property Merchant\Entity     $merchant
 * @property Customer\Entity     $customer
 * @property BankAccount\Entity  $bankAccount
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use NotesTrait;

    const ID                   = 'id';
    const MERCHANT_ID          = 'merchant_id';
    const STATUS               = 'status';
    const NAME                 = 'name';
    const DESCRIPTOR           = 'descriptor';
    const DESCRIPTION          = 'description';
    const AMOUNT_EXPECTED      = 'amount_expected';
    const AMOUNT_RECEIVED      = 'amount_received';
    const AMOUNT_PAID          = 'amount_paid';
    const AMOUNT_REVERSED      = 'amount_reversed';
    const BANK_ACCOUNT_ID      = 'bank_account_id';
    const VPA                  = 'vpa';
    const QR_CODE_ID           = 'qr_code_id';
    const CUSTOMER_ID          = 'customer_id';
    const NOTES                = 'notes';

    const RECEIVER_TYPES       = 'receiver_types';
    const RECEIVERS            = 'receivers';
    const TYPES                = 'types';
    const BANK_ACCOUNT         = 'bank_account';
    const NUMERIC              = 'numeric';

    const DELETED_AT           = 'deleted_at';

    protected $fillable = [
        self::NAME,
        self::STATUS,
        self::NOTES,
        self::DESCRIPTOR,
        self::DESCRIPTION,
        self::AMOUNT_EXPECTED,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ENTITY,
        self::STATUS,
        self::DESCRIPTION,
        self::AMOUNT_EXPECTED,
        self::NOTES,
        self::AMOUNT_PAID,
        self::CUSTOMER_ID,
        self::RECEIVERS,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
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
        self::NOTES                => [],
    ];

    protected static $modifiers = [
        self::NAME,
    ];

    protected $appends = [
        self::RECEIVERS,
    ];

    protected static $sign = 'va';

    protected $generateIdOnCreate = true;

    protected $entity = Constants::VIRTUAL_ACCOUNT;

    // ----------------------- Associations ------------------------------------

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function qrCode()
    {
        return $this->belongsTo('RZP\Models\QrCode\Entity');
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
            $label = $this->merchant->getBillingLabel();

            $label = substr(preg_replace('/[^a-zA-Z0-9 ]+/', '', $label), 0, 39);

            $input[self::NAME] = $label;
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

    public function hasQrCode()
    {
        return ($this->isAttributeNotNull(self::QR_CODE_ID));
    }

    public function hasCustomer()
    {
        return ($this->isAttributeNotNull(self::CUSTOMER_ID));
    }

    public function hasVpa()
    {
        return ($this->isAttributeNotNull(self::VPA));
    }

    // ----------------------- Getters -----------------------------------------

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

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

    public function getAmountReversed()
    {
        return $this->getAttribute(self::AMOUNT_REVERSED);
    }

    public function getExcessAmount()
    {
        $amountDeducted = ($this->getAmountReversed() + $this->getAmountExpected());

        $excessAmount = $this->getAmountReceived() - $amountDeducted;

        return max($excessAmount, 0);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getDescriptor()
    {
        return $this->getAttribute(self::DESCRIPTOR);
    }

    protected function getReceiversAttribute()
    {
        $receivers = [];

        foreach (Receiver::TYPES as $receiverType)
        {
            $assoc = studly_case($receiverType);

            $func = 'has' . $assoc;

            if ($this->$func() === true)
            {
                $receivers[] = $this->$assoc->toArrayPublic();
            }
        }

        return $receivers;
    }

    // ----------------------- Setters -----------------------------------------

    /**
     * Post-processing, VA amount fields are to be updated.
     * Status change is done inside incrementAmountPaid.
     *
     * @param Entity $bankTransfer
     */
    public function updateWithBankTransfer(BankTransfer\Entity $bankTransfer)
    {
        $this->incrementAmountPaid($bankTransfer->getAmount());

        $this->incrementAmountReceived($bankTransfer->getAmount());
    }

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

    public function incrementAmountPaid(int $amount)
    {
        $this->increment(self::AMOUNT_PAID, $amount);

        if (($this->hasAmountExpected() === true) and
            ($this->getAmountPaid() >= $this->getAmountExpected()))
        {
            $this->setStatus(Status::PAID);
        }
    }

    public function incrementAmountReceived(int $amount)
    {
        $this->increment(self::AMOUNT_RECEIVED, $amount);
    }

    public function incrementAmountReversed(int $amount)
    {
        $this->increment(self::AMOUNT_REVERSED, $amount);
    }
}
