<?php

namespace RZP\Models\Reversal;

use RZP\Models\Base;
use RZP\Models\Payout;
use RZP\Models\Merchant;
use RZP\Models\Transfer;
use RZP\Models\Customer;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Account;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Base\Traits\HasBalance;
use RZP\Models\Transfer\Traits\LinkedAccountNotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;
    use HasBalance;
    use LinkedAccountNotesTrait;

    const ID                    = 'id';
    const MERCHANT_ID           = 'merchant_id';
    const CUSTOMER_ID           = 'customer_id';
    const ENTITY_ID             = 'entity_id';
    const ENTITY_TYPE           = 'entity_type';
    const BALANCE_ID            = 'balance_id';
    const AMOUNT                = 'amount';
    const FEE                   = 'fee';
    const TAX                   = 'tax';
    const CURRENCY              = 'currency';
    const NOTES                 = 'notes';
    const TRANSACTION_ID        = 'transaction_id';
    const TRANSACTION_TYPE      = 'transaction_type';
    const TRANSFER              = 'transfer';
    const CHANNEL               = 'channel';
    const INITIATOR_ID          = 'initiator_id';
    const CUSTOMER_REFUND_ID    = 'customer_refund_id';
    // TODO: Need to fill UTR from FTS.
    const UTR                   = 'utr';

    // Input attribute const
    const LINKED_ACCOUNT_NOTES  = 'linked_account_notes';
    const REFUND_TO_CUSTOMER    = 'customer_refund';

    // Response attribute const
    const TRANSFER_ID           = 'transfer_id';
    const PAYOUT_ID             = 'payout_id';

    const REVERSAL_ID = 'reversal_id';

    const FEE_TYPE = 'fee_type';

    const ERROR                  = 'error';

    // Relations
    const TRANSACTION = 'transaction';

    // Any changes to this sign will affect LedgerStatus Job as well
    protected static $sign = 'rvrsl';

    protected $entity = 'reversal';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::FEE,
        self::TAX,
        self::CURRENCY,
        self::NOTES,
        self::CHANNEL,
        self::UTR,
        self::LINKED_ACCOUNT_NOTES,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::CUSTOMER_ID,
        self::TRANSACTION_ID,
        self::ENTITY_TYPE,
        self::ENTITY_ID,
        self::CHANNEL,
        self::BALANCE_ID,
        self::AMOUNT,
        self::FEE,
        self::TAX,
        self::CURRENCY,
        self::NOTES,
        self::TRANSFER_ID,
        self::PAYOUT_ID,
        self::INITIATOR_ID,
        self::CUSTOMER_REFUND_ID,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::TRANSFER_ID,
        self::PAYOUT_ID,
        self::AMOUNT,
        self::FEE,
        self::TAX,
        self::CURRENCY,
        self::NOTES,
        self::LINKED_ACCOUNT_NOTES,
        self::INITIATOR_ID,
        self::CUSTOMER_REFUND_ID,
        self::UTR,
        self::CREATED_AT,
        self::TRANSACTION_ID,
    ];

    protected $expanded = [
        self::TRANSACTION,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
        self::FEE    => 'int',
        self::TAX    => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEE,
        self::TAX,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::CUSTOMER_ID,
        self::PAYOUT_ID,
        self::TRANSFER_ID,
        self::LINKED_ACCOUNT_NOTES,
        self::NOTES,
        self::INITIATOR_ID,
        self::CUSTOMER_REFUND_ID,
        self::TRANSACTION_ID,
    ];

    protected $appends = [
        self::PAYOUT_ID,
        self::TRANSFER_ID,
    ];

    protected $defaults = [
        self::FEE   => 0,
        self::TAX   => 0,
        self::NOTES => [],
    ];

    // -------------------- Relations ---------------------------

    public function transaction()
    {
        return $this->morphTo();
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer\Entity::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function initiator()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function customerRefund()
    {
        return $this->belongsTo(Refund\Entity::class);
    }

    // -------------------- End Relations -----------------------

    // -------------------- Getters -----------------------------

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function getEntitySign(): string
    {
        return self::$sign;
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getFee()
    {
        return $this->getAttribute(self::FEE);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getCustomerId()
    {
        return $this->getAttribute(self::CUSTOMER_ID);
    }

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getTransactionType()
    {
        return $this->getAttribute(self::TRANSACTION_TYPE);
    }

    public function getCustomerRefundId()
    {
        return $this->getAttribute(self::CUSTOMER_REFUND_ID);
    }

    public function getInitiatorId()
    {
        return $this->getAttribute(self::INITIATOR_ID);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function hasTransaction()
    {
        return ($this->isAttributeNotNull(self::TRANSACTION_ID) === true);
    }

    // -------------------- End Getters --------------------------

    // -------------------- Setters ------------------------------

    public function setChannel($channel)
    {
        $this->setAttribute(self::CHANNEL, $channel);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setBalanceId($balanceId)
    {
        $this->setAttribute(self::BALANCE_ID, $balanceId);
    }

    public function setCurrency($currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setFee(int $fee)
    {
        assertTrue($fee >= 0);

        $this->setAttribute(self::FEE, $fee);
    }

    public function setTax(int $tax)
    {
        assertTrue($tax >= 0);

        $this->setAttribute(self::TAX, $tax);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setEntityId($entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    // -------------------- End Setters --------------------------

    // -------------------- Public Setters ------------------------------

    public function setTransactionId($txnId)
    {
        return $this->setAttribute(self::TRANSACTION_ID, $txnId);
    }

    public function setPublicTransferIdAttribute(array & $array)
    {
        if ($this->getEntityType() !== E::TRANSFER)
        {
            unset($array[self::TRANSFER_ID]);
        }
    }

    public function setPublicPayoutIdAttribute(array & $array)
    {
        if ($this->getEntityType() !== E::PAYOUT)
        {
            unset($array[self::PAYOUT_ID]);
        }
    }

    public function setPublicCustomerIdAttribute(array & $array)
    {
        $customerId = $this->getAttribute(self::CUSTOMER_ID);

        //
        // customer_id is used only in the customer wallet payout reversals flow. We do not want
        // to expose this field in general
        //
        if ($customerId === null)
        {
            unset($array[self::CUSTOMER_ID]);

            return;
        }

        $array[self::CUSTOMER_ID] = Customer\Entity::getSignedIdOrNull($customerId);
    }

    public function setPublicLinkedAccountNotesAttribute(array & $array)
    {
        if ($this->getEntityType() !== E::TRANSFER)
        {
            unset($array[self::LINKED_ACCOUNT_NOTES]);
        }
    }

    public function setPublicNotesAttribute(array & $array)
    {
        if ($this->getEntityType() !== E::TRANSFER)
        {
            unset($array[self::NOTES]);
        }
    }

    public function setPublicInitiatorIdAttribute(array & $array)
    {
        $initiatorId = $this->getAttribute(self::INITIATOR_ID);

        if (empty($this->initiator) === false and ($this->initiator->isLinkedAccount() === true))
        {
            $initiatorId = Account\Entity::getSignedIdOrNull($initiatorId);
        }

        $array[self::INITIATOR_ID] = $initiatorId;

        if ($this->getEntityType() !== E::TRANSFER)
        {
            unset($array[self::INITIATOR_ID]);
        }
    }

    public function setPublicCustomerRefundIdAttribute(array & $array)
    {
        $customerRefundId = $this->getAttribute(self::CUSTOMER_REFUND_ID);

        $array[self::CUSTOMER_REFUND_ID] = Refund\Entity::getSignedIdOrNull($customerRefundId);

        if ($this->getEntityType() !== E::TRANSFER)
        {
            unset($array[self::CUSTOMER_REFUND_ID]);
        }
    }

    public function setPublicTransactionIdAttribute(array & $array)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        if ($basicAuth->isAccountingIntegrationsApp() === true)
        {
            // similar behaviour as of payout
            $array[self::TRANSACTION_ID] = Transaction\Entity::getSignedIdOrNull($this->getTransactionId());
        }
        else
        {
            unset($array[self::TRANSACTION_ID]);
        }

    }

    // -------------------- End Public Setters --------------------------

    // -------------------- Accessors ------------------------------

    public function getPayoutIdAttribute()
    {
        if ($this->getEntityType() === E::PAYOUT)
        {
            return Payout\Entity::getSignedIdOrNull($this->getEntityId());
        }

        return null;
    }

    public function getTransferIdAttribute()
    {
        if ($this->getEntityType() === E::TRANSFER)
        {
            return Transfer\Entity::getSignedIdOrNull($this->getEntityId());
        }

        return null;
    }

    // -------------------- End Accessors ------------------------------

    public function setIgnoreRelationsForPayoutServiceReversals()
    {
        $this->ignoredRelations = [self::ENTITY];
    }
}
