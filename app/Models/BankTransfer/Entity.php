<?php

namespace RZP\Models\BankTransfer;

use Razorpay\IFSC\IFSC;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Transaction;
use RZP\Models\VirtualAccount;
use RZP\Models\Bank\BankCodes;
use Razorpay\Trace\Facades\Trace;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Role\TenantRoles;
use RZP\Models\Base\Traits\HasBalance;

/**
 * @property Payment\Entity        $payment
 * @property Merchant\Entity       $merchant
 * @property VirtualAccount\Entity $virtualAccount
 * @property BankAccount\Entity    $payerBankAccount
 */
class Entity extends Base\PublicEntity
{
    use HasBalance;

    const ID                 = 'id';
    const PAYMENT_ID         = 'payment_id';
    const MERCHANT_ID        = 'merchant_id';

    // Details of the sender bank account
    const PAYER_NAME            = 'payer_name';
    const PAYER_ACCOUNT         = 'payer_account';
    const PAYER_ACCOUNT_TYPE    = 'payer_account_type';
    const PAYER_IFSC            = 'payer_ifsc';
    const PAYER_ADDRESS         = 'payer_address';
    const PAYER_BANK_ACCOUNT    = 'payer_bank_account';
    const PAYER_BANK_ACCOUNT_ID = 'payer_bank_account_id';
    const PAYER_BANK_NAME       = 'payer_bank_name';

    // Details of the receiver bank account
    const PAYEE_NAME         = 'payee_name';
    const PAYEE_ACCOUNT      = 'payee_account';
    const PAYEE_IFSC         = 'payee_ifsc';

    const VIRTUAL_ACCOUNT_ID = 'virtual_account_id';
    const BALANCE_ID         = 'balance_id';
    const VIRTUAL_ACCOUNT    = 'virtual_account';

    const TRANSACTION_ID     = 'transaction_id';

    const AMOUNT             = 'amount';

    // Modes: NEFT, RTGS, IMPS, IFT
    const MODE               = 'mode';

    // Bank reference number
    const UTR                = 'utr';

    // Public alias for UTR
    const BANK_REFERENCE     = 'bank_reference';

    // Time of transaction
    const TIME               = 'time';

    // Remarks field
    const DESCRIPTION        = 'description';

    // Currency of payment in notification
    const CURRENCY           = 'currency';

    // Attempts made by provider to notify
    const ATTEMPT            = 'attempt';

    // Indicates whether the bank transfer corresponds
    // to an active virtual account on our side. If
    // false, this transfer will need to be refunded
    const EXPECTED          = 'expected';
    const UNEXPECTED_REASON = 'unexpected_reason';

    // All entities are created and process in the bank transfer process flow.
    // In the notify flow, we simply mark the bank transfer as a confirmed one.
    const NOTIFIED           = 'notified';

    // Original request contains this key as input, it is actually the UTR.
    // This is used to generate the value for the UTR field.
    const REQ_UTR            = 'transaction_id';

    const NARRATION          = 'narration';

    // Input keys
    const REFUND_ID                     = 'refund_id';
    const GATEWAY                       = 'gateway';
    const FIRST_TIME_ON_TEST_MODE       = 'first_time_on_test_mode';

    const INVALID_ACC_CREDIT_NARRATION  = 'ACC DOESNT EXIST';
    const MAX_NARRATION_LENGTH          = 39;
    const SPECIAL_IFSC_CODE             = 'RAZR0000001';
    const MAX_DESCRIPTION_LENGTH        = 255;

    const REQUEST_SOURCE                = 'request_source';
    const REQUEST_FROM                  = 'request_from';
    const INPUT                         = 'input';
    const FILE                          = 'file';
    const CALLBACK                      = 'callback';
    const SOURCE                        = 'source';
    const STATUS                        = 'status';

    protected $requestSource;

    protected $fillable = [
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
        self::NARRATION,
        self::STATUS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::PAYMENT_ID,
        self::MODE,
        self::BANK_REFERENCE,
        self::AMOUNT,
        self::PAYER_BANK_ACCOUNT,
        self::VIRTUAL_ACCOUNT_ID,
        self::PAYEE_ACCOUNT,
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
        self::GATEWAY,
        self::NARRATION,
        self::UTR,
        self::TRANSACTION_ID,
        self::TIME,
        self::EXPECTED,
        self::UNEXPECTED_REASON,
        self::NOTIFIED,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STATUS,
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
        self::DESCRIPTION,
        self::PAYEE_ACCOUNT,
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
        self::BANK_REFERENCE,
        self::PAYER_BANK_ACCOUNT,
        self::PAYEE_ACCOUNT,
    ];

    protected $pii = [
        self::PAYEE_ACCOUNT,
        self::PAYER_NAME,
        self::PAYER_ACCOUNT,
    ];

    // Any changes to this sign will affect LedgerStatus Job as well
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

    /**
     * For business banking there would be a transaction created per transfer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function transaction()
    {
        return $this->morphOne(Transaction\Entity::class, 'source', 'type', 'entity_id');
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

    public function setPublicBankReferenceAttribute(array & $array)
    {
        $array[self::BANK_REFERENCE] = $array[self::UTR];
    }

    public function setPublicPayerBankAccountAttribute(array & $array)
    {
        if ($this->merchant->isFeatureEnabled(Feature\Constants::HIDE_VA_PAYER_BANK_DETAIL) === true)
        {
            unset($array[self::PAYER_BANK_ACCOUNT]);

            return;
        }

        if ($this->getPayerBankAccountId() !== null)
        {
            $array[self::PAYER_BANK_ACCOUNT] = $this->payerBankAccount->toArrayPublic();
        }
    }

    public function setPublicPayeeAccountAttribute(array & $array)
    {
        /** @var BasicAuth $basicAuth */
        $basicAuth = app('basicauth');

        // This is required because payee_Account is required in the
        // transaction.created webhook fired on bank_transfer
        // This currently is required only for RX, not for PG
        if (($basicAuth->isAdminAuth() === false) and
            ($this->isBalanceTypeBanking() === false))
        {
            unset($array[self::PAYEE_ACCOUNT]);
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

        $amount = (int) number_format(($amount * 100), 0, '.', '');

        $this->attributes[self::AMOUNT] = $amount;
    }

    // -------------------------- Modifiers ------------------------------------

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

    public function modifyPayeeAccount(array & $input)
    {
        //
        // Removing spaces, since Kotak credits us even when
        // customer enters R A Z O R P A Y 1 2 3. Can change to
        // remove all whitespaces later, if required to do so.
        //

        if (isset($input[self::PAYEE_ACCOUNT]) === true)
        {
            $input[self::PAYEE_ACCOUNT] = str_replace(' ', '', $input[self::PAYEE_ACCOUNT]);

            $input[self::PAYEE_ACCOUNT] = strtoupper($input[self::PAYEE_ACCOUNT]);
        }
    }

    // -------------------------- Getters --------------------------------------

    public function getMethod()
    {
        return Payment\Method::BANK_TRANSFER;
    }

    public function getVirtualAccountId()
    {
        return $this->getAttribute(self::VIRTUAL_ACCOUNT_ID);
    }

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

        return IFSC::getBankName(strtoupper($ifsc));
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

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getMappedPayerIfsc()
    {
        $ifsc = $this->getPayerIfsc();

        if ((strlen($ifsc) !== BankAccount\Entity::IFSC_CODE_LENGTH) and
            ($this->getMode() === Mode::IMPS))
        {
            if ($this->getGateway() === VirtualAccount\Provider::KOTAK)
            {
                 /**
                  *  In can of Kotak, we get Bank Code followed by 10 digit Mobile number.
                  *  Bank Codes vary from 3 digits to 5 digits
                  *  but we are only taking first 3 digits into consideration.
                  */
                $impsBankCode = substr($ifsc, 0, 3);

                $ifsc = BankCodes::getIfscForImpsBankCode($impsBankCode);

                if($ifsc === null)
                {
                    Trace::info(TraceCode::BANK_TRANSFER_BANK_CODE_MISSING, ['bank_code' => $impsBankCode]);
                }
            }
            else if ($this->getGateway() === VirtualAccount\Provider::YESBANK)
            {
                $nbin = $ifsc;

                $ifsc = BankCodes::getIfscForNbin($nbin);

                if($ifsc === null)
                {
                    Trace::info(TraceCode::BANK_TRANSFER_NBIN_CODE_MISSING, ['nbin' => $nbin]);
                }
            }
        }
        return $ifsc;
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getPaymentId()
    {
        return $this->getAttribute(self::PAYMENT_ID);
    }

    public function getPayerBankAccountId()
    {
        return $this->getAttribute(self::PAYER_BANK_ACCOUNT_ID);
    }

    public function isNotified()
    {
        return $this->getAttribute(self::NOTIFIED);
    }

    public function isExpected()
    {
        return $this->getAttribute(self::EXPECTED);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getTransactionId()
    {
        $transaction = $this->transaction;

        return optional($transaction)->getId();
    }

    public function getTransactionCurrency()
    {
        $transaction = $this->transaction;

        return optional($transaction)->getCurrency();
    }

    public function getTransactionFee()
    {
        $transaction = $this->transaction;

        return optional($transaction)->getFee();
    }

    public function getTransactionTax()
    {
        $transaction = $this->transaction;

        return optional($transaction)->getTax();
    }

    public function getNarration()
    {
        return $this->getAttribute(self::NARRATION);
    }

    public function getUnexpectedReason()
    {
        return $this->getAttribute(self::UNEXPECTED_REASON);
    }

    public function getPii()
    {
        return $this->pii;
    }

    public function getRequestSource()
    {
        return $this->requestSource;
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

    public function setTransactionId(string $transactionId)
    {
        $this->setAttribute(self::TRANSACTION_ID, $transactionId);
    }

    public function setNotified(bool $notified)
    {
        $this->setAttribute(self::NOTIFIED, $notified);
    }

    public function setCustomerName(string $name)
    {
        $this->setAttribute(self::PAYER_NAME, $name);
    }

    public function setPayerIfsc(string $ifsc)
    {
        $this->setAttribute(self::PAYER_IFSC, $ifsc);
    }

    public function setUnexpectedReason(string $unexpectedReason)
    {
        $this->setAttribute(self::UNEXPECTED_REASON, $unexpectedReason);
    }

    public function setGateway(string $gateway)
    {
        $this->setAttribute(self::GATEWAY, $gateway);
    }

    public function setRequestSource($requestSource)
    {
        $this->requestSource = $requestSource;
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function getRefundNarration()
    {
        $utr = $this->getUtr();

        $availableLength = self::MAX_NARRATION_LENGTH - strlen($utr) - 1;

        if ($this->isExpected() === true)
        {
            $billingLabel = $this->merchant->getBillingLabel();

            $label = substr($billingLabel, 0, $availableLength);
        }
        else
        {
            $label = self::INVALID_ACC_CREDIT_NARRATION;
        }

        return $label . '-' . $utr;
    }

    public function shouldNotifyTxnViaSms(): bool
    {
        return false;
    }

    public function shouldNotifyTxnViaEmail(): bool
    {
        return ($this->isBalanceTypeBanking() === true);
    }

    public function toArrayTrace(): array
    {
        $data = $this->toArray();

        foreach ($this->pii as $piiField)
        {
            if (isset($data[$piiField]) === false)
            {
                continue;
            }

            switch ($piiField)
            {
                case self::PAYEE_ACCOUNT:
                    $payeeAccount = $data[Entity::PAYEE_ACCOUNT];

                    $data[Entity::PAYEE_ACCOUNT . '_prefix']        = substr($payeeAccount, 0, 8);
                    $data[Entity::PAYEE_ACCOUNT . '_descriptor']    = substr($payeeAccount, 8, strlen($payeeAccount));

                    break;

                default:
                    break;
            }

            unset($data[$piiField]);
        }

        return $data;
    }

    public function getFilterForRole(string $role): array
    {
        if ($role === TenantRoles::ENTITY_BANKING)
        {
            return [
                'balance_type'  =>   Merchant\Balance\Type::BANKING
            ];
        }

        return [];
    }
}
