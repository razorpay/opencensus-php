<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;
use Razorpay\IFSC\IFSC;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\VirtualAccount;

/**
 * @property Payment\Entity        $payment
 * @property Merchant\Entity       $merchant
 * @property VirtualAccount\Entity $virtualAccount
 * @property BankAccount\Entity    $payerBankAccount
 */
class Entity extends Base\PublicEntity
{
    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const MERCHANT_ID        = 'merchant_id';

    // Details of the sender bank account
    const PAYER_NAME            = 'payer_name';
    const PAYER_ACCOUNT         = 'payer_account';
    const PAYER_IFSC            = 'payer_ifsc';
    const PAYER_BANK_ACCOUNT    = 'payer_bank_account';
    const PAYER_BANK_ACCOUNT_ID = 'payer_bank_account_id';
    const PAYER_BANK_NAME       = 'payer_bank_name';

    // Details of the receiver bank account
    const PAYEE_ACCOUNT      = 'payee_account';
    const PAYEE_IFSC         = 'payee_ifsc';

    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';
    const VIRTUAL_ACCOUNT    = 'virtual_account';

    const AMOUNT             = 'amount';

    // Modes: NEFT, RTGS, IMPS, IFT
    const MODE               = 'mode';

    // Bank reference number
    const UTR                = 'utr';

    // Time of transaction
    const TIME               = 'time';

    // Remarks field
    const DESCRIPTION        = 'description';

    // Indicates whether the bank transfer corresponds
    // to an active virtual account on our side. If
    // false, this transfer will need to be refunded
    const EXPECTED           = 'expected';

    // All entities are created and process in the bank transfer process flow.
    // In the notify flow, we simply mark the bank transfer as a confirmed one.
    const NOTIFIED           = 'notified';

    // Original request contains this key as input, it is actually the UTR.
    // This is used to generate the value for the UTR field.
    const REQ_UTR            = 'transaction_id';

    const SPECIAL_IFSC_CODE  = 'RAZR0000001';

    const MAX_DESCRIPTION_LENGTH = 255;

    protected $fillable = [
        self::PAYMENT_ID,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::MODE,
        self::UTR,
        self::TIME,
        self::AMOUNT,
        self::DESCRIPTION,
    ];

    protected $public = [
        self::PAYMENT_ID,
        // This can be added later, upon request
        // self::MODE,
        // self::UTR,
        self::AMOUNT,
        self::PAYER_BANK_ACCOUNT,
        self::VIRTUAL_ACCOUNT_ID,
        self::VIRTUAL_ACCOUNT,
    ];

    protected $appends = [
        self::PAYER_BANK_NAME,
    ];

    protected $visible = [
        self::ID,
        self::PAYMENT_ID,
        self::MERCHANT_ID,
        self::VIRTUAL_ACCOUNT_ID,
        self::AMOUNT,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
        self::PAYER_IFSC,
        self::PAYER_BANK_ACCOUNT_ID,
        self::PAYEE_ACCOUNT,
        self::PAYEE_IFSC,
        self::PAYER_BANK_NAME,
        self::DESCRIPTION,
        self::MODE,
        self::UTR,
        self::TIME,
        self::EXPECTED,
        self::NOTIFIED,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::AMOUNT   => 'int',
        self::EXPECTED => 'bool',
        self::NOTIFIED => 'bool',
    ];

    protected static $generators = [
        self::UTR,
    ];

    protected static $modifiers = [
        self::AMOUNT,
        self::DESCRIPTION,
    ];

    protected $defaults = [
        self::EXPECTED => false,
        self::NOTIFIED => false,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYMENT_ID,
        self::MODE,
    ];

    protected static $sign = 'bt';

    protected $entity = Constants\Entity::BANK_TRANSFER;

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

    public function payerBankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    // ----------------------- Generators --------------------------------------

    // Kotak is sending us transaction_id instead of UTR
    // We unset this and set UTR early in the flow
    public function generateUtr($input)
    {
        $this->setAttribute(self::UTR, $input[self::REQ_UTR]);
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

    public function setPublicPaymentIdAttribute(array & $array)
    {
        if (isset($array[self::PAYMENT_ID]) === true)
        {
            $paymentId = $array[self::PAYMENT_ID];

            $array[self::PAYMENT_ID] = Payment\Entity::getSignedId($paymentId);
        }
    }

    public function setPublicModeAttribute(array & $array)
    {
        $array[self::MODE] = strtoupper($array[self::MODE]);
    }

    // -------------------------- Modifiers ------------------------------------

    public function modifyAmount(array & $input)
    {
        //
        // If you're wondering why this is here, run "(int) (579.3 * 100)" in tinker
        //
        // The value of (579.3 * 100) is actually stored as 57929.999... and casting
        // that to an integer just dumps the decimal part and ruins everything.
        //
        // testBankTransferFloatingPointImprecision exists to check against this.
        //

        if (isset($input[self::AMOUNT]) === true)
        {
            $input[self::AMOUNT] = (int) number_format(($input[self::AMOUNT] * 100), 0, '.', '');
        }

    }

    public function modifyDescription(array & $input)
    {
        //
        // This field is used in payment description, so we truncate to the limit
        //

        if (isset($input[self::DESCRIPTION]) === true)
        {
            $input[self::DESCRIPTION] = substr($input[self::DESCRIPTION], 0, self::MAX_DESCRIPTION_LENGTH);
        }
    }

    // -------------------------- Getters --------------------------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getPayerBankNameAttribute()
    {
        $ifsc = $this->getAttribute(self::PAYER_IFSC);

        if ($ifsc === null)
        {
            return null;
        }

        if ($ifsc === self::SPECIAL_IFSC_CODE)
        {
            return 'Razorpay';
        }

        return IFSC::getBankName($ifsc);
    }

    public function getPayerName()
    {
        return $this->getAttribute(self::PAYER_NAME);
    }

    public function getPayeeAccount()
    {
        return $this->getAttribute(self::PAYEE_ACCOUNT);
    }

    public function getPayeeIfsc()
    {
        return $this->getAttribute(self::PAYEE_IFSC);
    }

    public function getPayerAccount()
    {
        return $this->getAttribute(self::PAYER_ACCOUNT);
    }

    public function getPayerIfsc()
    {
        return $this->getAttribute(self::PAYER_IFSC);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function isNotified()
    {
        return $this->getAttribute(self::NOTIFIED);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    // ----------------------- Setters -----------------------------------------

    public function setExpected(bool $expected)
    {
        $this->setAttribute(self::EXPECTED, $expected);
    }

    public function setUtr(string $utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setNotified(bool $notified)
    {
        $this->setAttribute(self::NOTIFIED, $notified);
    }

    public function setCustomerName(string $name)
    {
        $this->setAttribute(self::PAYER_NAME, $name);
    }
}
