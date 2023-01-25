<?php

namespace RZP\Models\VirtualAccount;

use Carbon\Carbon;
use RZP\Models\Vpa;
use RZP\Models\Base;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\BankAccount;
use RZP\Models\BankTransfer;
use RZP\Constants\Entity as Constants;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Exception\BadRequestException;
use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\OfflinePayment;
use RZP\Models\Order\Repository as OrderRepository;
use RZP\Trace\TraceCode;

/**
 * @property Vpa\Entity          $vpa
 * @property Merchant\Entity     $merchant
 * @property Customer\Entity     $customer
 * @property BankAccount\Entity  $bankAccount
 * @property BankAccount\Entity  $bankAccount2
 */
class Entity extends Base\PublicEntity
{
    use SoftDeletes;
    use NotesTrait;
    use HasBalance;

    const STATUS               = 'status';
    const NAME                 = 'name';
    const DESCRIPTOR           = 'descriptor';
    const DESCRIPTION          = 'description';
    const AMOUNT_EXPECTED      = 'amount_expected';
    const AMOUNT_RECEIVED      = 'amount_received';
    const AMOUNT_PAID          = 'amount_paid';
    const AMOUNT_REVERSED      = 'amount_reversed';
    const BANK_ACCOUNT_ID      = 'bank_account_id';
    const BANK_ACCOUNT_ID2     = 'bank_account_id_2';
    const OFFLINE_CHALLAN_ID   = 'offline_challan_id';
    const VPA_ID               = 'vpa_id';
    const QR_CODE_ID           = 'qr_code_id';
    const CUSTOMER_ID          = 'customer_id';
    const ENTITY_ID            = 'entity_id';
    const ENTITY_TYPE          = 'entity_type';
    const BALANCE_ID           = 'balance_id';
    const NOTES                = 'notes';
    const CUSTOMER             = 'customer';

    const RECEIVER_TYPE        = 'receiver_type';
    const RECEIVER_TYPES       = 'receiver_types';
    const RECEIVERS            = 'receivers';
    const TYPES                = 'types';
    const BANK_ACCOUNT         = 'bank_account';
    const VPA                  = 'vpa';
    const QR_CODE              = 'qr_code';
    const NUMERIC              = 'numeric';
    const CLOSE_BY             = 'close_by';
    const CLOSED_AT            = 'closed_at';

    const DELETED_AT           = 'deleted_at';

    // order_id is a valid request parameter, but is mapped to entity_id
    const ORDER_ID             = 'order_id';

    // For TPV details in request
    const ALLOWED_PAYERS       = 'allowed_payers';

    // Used for creating shared virtual account
    const SHARED_ID            = 'ShrdVirtualAcc';
    const SHARED_ID_BANKING    = 'HMwb1lgZD9N5Gm';

    const SOURCE               = 'source';

    const PAYEE_ACCOUNT        = 'payee_account';

    protected $fillable = [
        self::NAME,
        self::STATUS,
        self::NOTES,
        self::DESCRIPTOR,
        self::DESCRIPTION,
        self::AMOUNT_EXPECTED,
        self::AMOUNT_RECEIVED,
        self::AMOUNT_PAID,
        self::CLOSE_BY,
        self::SOURCE,
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
        self::ALLOWED_PAYERS,
        self::CLOSE_BY,
        self::CLOSED_AT,
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
        self::ALLOWED_PAYERS,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::CLOSE_BY,
        self::CLOSED_AT,
    ];

    protected static $sign = 'va';

    protected $generateIdOnCreate = true;

    protected $entity = Constants::VIRTUAL_ACCOUNT;

    // ----------------------- Associations ------------------------------------
    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity')->withTrashed();
    }

    public function bankAccount2()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity', 'bank_account_id_2')->withTrashed();
    }

    public function offlineChallan()
    {
        return $this->belongsTo('RZP\Models\OfflineChallan\Entity');
    }

    public function qrCode()
    {
        return $this->belongsTo('RZP\Models\QrCode\Entity');
    }

    public function vpa()
    {
        return $this->belongsTo('RZP\Models\Vpa\Entity')->withTrashed();
    }

    public function customer()
    {
        return $this->belongsTo('RZP\Models\Customer\Entity');
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function virtualAccountTpv()
    {
        return $this->hasMany('RZP\Models\VirtualAccountTpv\Entity');
    }

    public function entity()
    {
        return $this->morphTo();
    }

    /**
     * Points to the pivot table entity `entityOrigin` for the payment
     */
    public function entityOrigin()
    {
        return $this->morphOne(\RZP\Models\EntityOrigin\Entity::class, 'entity');
    }

    // ----------------------- Modifiers ---------------------------------------

    public function modifyName(& $input)
    {
        $priorityOrderHighToLow = [
            $input[self::NAME] ?? null,
            $this->merchant->getBillingLabel(),
            $this->merchant->getName(),
        ];

        $vaName = $this->getHighestPriorityName($this->merchant, $input);

        if(empty($vaName) === false)
        {
            $input[self::NAME] = $vaName;
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

    public function hasBankAccount2()
    {
        return ($this->isAttributeNotNull(self::BANK_ACCOUNT_ID2));
    }

    public function hasOfflineChallan()
    {
        return ($this->isAttributeNotNull(self::OFFLINE_CHALLAN_ID));
    }

    public function hasQrCode()
    {
        return ($this->isAttributeNotNull(self::QR_CODE_ID));
    }

    public function hasCustomer()
    {
        return ($this->isAttributeNotNull(self::CUSTOMER_ID));
    }

    public function hasOrder()
    {
        return ($this->isAttributeNotNull(self::ENTITY_ID));
    }

    public function hasVpa()
    {
        return ($this->isAttributeNotNull(self::VPA_ID));
    }

    public function hasPos()
    {
        return false;
    }

    // ----------------------- Getters -----------------------------------------

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
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

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getDescriptor()
    {
        return $this->getAttribute(self::DESCRIPTOR);
    }

    public function getCloseBy()
    {
        return $this->getAttribute(self::CLOSE_BY);
    }

    public function getClosedAt()
    {
        return $this->getAttribute(self::CLOSED_AT);
    }

    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    protected function getReceiversAttribute()
    {
        $receivers = [];

        $isRemoveBankAccount = false;

        foreach (Receiver::TYPES as $receiverType)
        {
            $assoc = studly_case($receiverType);

            $func = 'has' . $assoc;

            if ($this->$func() === true)
            {
                $receivers[] = $this->$assoc->toArrayPublic();
            }
        }

        if ($this->hasBankAccount2() === true)
        {
            $bankAccount2 = $this->bankAccount2->toArrayPublic();

            $ifsc = $bankAccount2[BankAccount\Entity::IFSC];

            $bankAccount2Array[] = $bankAccount2;

            if ($ifsc === Provider::IFSC[Provider::RBL])
            {
                $receivers = array_merge($bankAccount2Array, $receivers);
            }
            else
            {
                $receivers = array_merge($receivers, $bankAccount2Array);
            }
            $isRemoveBankAccount = true;
        }

        /*
         * As we are not supporting Yesbank and ICICI VA's
         * we need to remove them from the Existing Fetch API
         */
        if (($this->isBalanceTypeBanking() === false) and ($isRemoveBankAccount === true) and (sizeof($receivers) > 1))
        {
            foreach ($receivers as $index => $receiverObject)
            {
                if (array_key_exists(BankAccount\Entity::IFSC, $receiverObject) and
                    (in_array($receiverObject[BankAccount\Entity::IFSC], Provider::getUnsuportedProviderByRazorpay())))
                {
                    unset($receivers[$index]);
                }
            }
            $receivers = array_values($receivers);
        }

        return $receivers;
    }

    protected function getAllowedPayersAttribute()
    {
        $allowedPayers = [];

        foreach ($this->virtualAccountTpv()->get() as $virtualAccountTpv)
        {
            $allowedPayers[] = $virtualAccountTpv->getAllowedPayerDetails();
        }

        return $allowedPayers;
    }

    public function getEntityAttribute()
    {
        $entity = null;

        if ($this->relationLoaded('entity') === true)
        {
            $entity = $this->getRelation('entity');
        }

        if ($entity !== null)
        {
            return $entity;
        }

        if ($this->getEntityType() === Constants::ORDER)
        {
            $entity = $this->entity()->with('offers')->first();
        }
        else if ($this->getEntityId() !== null)
        {
            $entity = $this->entity()->first();
        }

        if (empty($entity) === false)
        {
            return $entity;
        }

        if ($this->getEntityType() === Constants::ORDER)
        {
            $order = (new OrderRepository())->findOrFailPublic($this->getEntityId());

            $this->entity()->associate($order);

            return $order;
        }

        return null;
    }


    public function getReceiverBuilder()
    {
        return new Receiver($this);
    }

    // ----------------------- Setters -----------------------------------------

    /**
     * Post-processing, VA amount fields are to be updated.
     * Status change is done inside incrementAmountPaid.
     * @param BankTransfer\Entity $bankTransfer
     */
    public function updateWithBankTransfer(BankTransfer\Entity $bankTransfer)
    {
        $paidAmount = $bankTransfer->payment->getAdjustedAmountWrtCustFeeBearer();

        if ($paidAmount < 0)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BANK_TRANSFER_FEE_CALCULATED_GREATER_THAN_PAYMENT_AMOUNT,
                BankTransfer\Entity::AMOUNT,
                $bankTransfer->getAmount()
            );
        }

        $this->incrementAmountPaid($paidAmount);
        $this->incrementAmountReceived($paidAmount);
    }

    /**
     * Updates aggregate stats and status changes of
     * virtual account wrt new bank transfer done.
     *
     * @param  BankTransfer\Entity $bankTransfer
     */
    public function updateWithBankTransferForBanking(BankTransfer\Entity $bankTransfer)
    {
        $paidAmount = $bankTransfer->getAmount();

        $this->incrementAmountPaid($paidAmount);
        $this->incrementAmountReceived($paidAmount);
    }

    public function updateWithOfflinePayment(OfflinePayment\Entity $offlinePayment)
    {
        $paidAmount = $offlinePayment->payment->getAdjustedAmountWrtCustFeeBearer();

        if ($paidAmount < 0)
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_OFFLINE_HIGHER_PAYMENT_AMOUNT,
                $paidAmount
            );

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_FEES_GREATER_THAN_AMOUNT,
                OfflinePayment\Entity::AMOUNT,
                $offlinePayment->getAmount()
            );
        }

        $this->incrementAmountPaid($paidAmount);
        $this->incrementAmountReceived($paidAmount);
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

    public function setClosedAt(int $closedAt)
    {
        $this->setAttribute(self::CLOSED_AT, $closedAt);
    }

    public function setSource(string $source)
    {
        return $this->setAttribute(self::SOURCE, $source);
    }

    public function setDescriptor(string $value)
    {
        return $this->setAttribute(self::DESCRIPTOR, $value);
    }

    protected function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    public function incrementAmountPaid(int $amount)
    {
        $this->increment(self::AMOUNT_PAID, $amount);
    }

    public function incrementAmountReceived(int $amount)
    {
        $this->increment(self::AMOUNT_RECEIVED, $amount);
    }

    public function incrementAmountReversed(int $amount)
    {
        $this->increment(self::AMOUNT_REVERSED, $amount);
    }

    public function isDueToBeClosed()
    {
        $currentTime = Carbon::now()->getTimestamp();

        if (($this->getCloseBy() !== null) and
            ($this->getCloseBy() < $currentTime) === true)
        {
            return true;
        }
    }

    public function isReceiverPresent(string $receiverType)
    {
        $assoc = studly_case($receiverType);

        $func  = 'has' . $assoc;

        return $this->$func();
    }

    public function isActive(): bool
    {
        return $this->getStatus() === Status::ACTIVE;
    }

    public function isClosed()
    {
        return $this->getStatus() === Status::CLOSED;
    }

    public function isTpvEnabled()
    {
        return $this->virtualAccountTpv()->count() !== 0;
    }

    public function toArrayPublic()
    {
        $array = parent::toArrayPublic();

        if ($this->isTpvEnabled() === false)
        {
            unset($array[self::ALLOWED_PAYERS]);
        }

        return $array;
    }

    public function getHighestPriorityName($merchant, $data)
    {
        $priorityOrderHighToLow = [
            $data[Entity::NAME] ?? null,
            $merchant->getBillingLabel(),
            $merchant->getName()
        ];

        foreach ($priorityOrderHighToLow as $value)
        {
            if (empty($value) === false)
            {
                $sanitizedValue = trim(substr(preg_replace('/[^a-zA-Z0-9 ]+/', '', $value), 0, 39));

                if (strlen($sanitizedValue) > 2)
                {
                    return $sanitizedValue;
                }
            }
        }
    }
}
