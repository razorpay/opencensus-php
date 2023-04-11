<?php

namespace RZP\Models\Transaction\Statement\Ledger\Statement;

use App;
use Razorpay\Trace\Logger;

use RZP\Models\Payout;
use RZP\Models\External;
use RZP\Trace\TraceCode;
use RZP\Models\Adjustment;
use RZP\Models\BankTransfer;
use RZP\Models\CreditTransfer;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
use RZP\Models\Base\PublicEntity;
Use RZP\Models\Transaction\Statement\Ledger\Journal;
use RZP\Models\Transaction\Statement\Ledger\LedgerEntry;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\Ledger\Statement
 */
class Entity extends LedgerEntry\Entity
{
    // Derived attributes
    const ACCOUNT_NUMBER    = 'account_number';

    // Additional input/output attributes
    const CONTACT_ID      = 'contact_id';
    const BALANCE_ID      = 'balance_id';
    const PAYOUT_ID       = 'payout_id';
    const CONTACT_NAME    = 'contact_name';
    const CONTACT_PHONE   = 'contact_phone';
    const CONTACT_EMAIL   = 'contact_email';
    const CONTACT_TYPE    = 'contact_type';
    const PAYOUT_PURPOSE  = 'payout_purpose';
    const MODE            = 'mode';
    const FUND_ACCOUNT_ID = 'fund_account_id';
    const UTR             = 'utr';
    const CREDIT          = 'credit';
    const DEBIT           = 'debit';
    const POSTED_AT       = 'posted_at';

    //Partial Search
    const CONTACT_PHONE_PS  = 'contact_phone_ps';
    const CONTACT_EMAIL_PS  = 'contact_email_ps';

    //Input key to support search using adjustment_id
    const ADJUSTMENT_ID   = 'adjustment_id';

    const ACTION = 'action';

    //Used Exclusively for the ES raw searching of Email
    const CONTACT_EMAIL_RAW = 'contact_email.raw';

    const ACCOUNT_BALANCE   = 'account_balance';

    protected $entity = 'ledger_statement';

    protected $primaryKey = self::JOURNAL_ID;

    protected $public = [
        self::ID,
        self::ENTITY,
        self::ACCOUNT_NUMBER,
        self::AMOUNT,
        self::CURRENCY,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::CREATED_AT,
        self::SOURCE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
    ];

    protected $amounts = [
        self::DEBIT,
        self::CREDIT,
    ];

    protected $casts = [
        self::CREDIT              => 'int',
        self::DEBIT               => 'int',
        self::AMOUNT              => 'int',
        self::BALANCE             => 'int',
    ];

    protected $appends = [
        self::ACCOUNT_NUMBER,
        self::CREDIT,
        self::DEBIT,
    ];

    public function accountBalance()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Balance\Entity::class, Entity::MERCHANT_ID, Entity::MERCHANT_ID)
            ->where(Balance\Entity::TYPE, Balance\Type::BANKING)
            ->where(Balance\Entity::ACCOUNT_TYPE, Balance\AccountType::SHARED);
    }

    public function source()
    {
        return $this->morphTo('source', Journal\Entity::TRANSACTOR_TYPE, Journal\Entity::TRANSACTOR_INTERNAL_ID);
    }

    /***
     * Public id for ledger statement is Journal Id
     *
     */
    public function getId()
    {
        return $this->getAttribute(self::JOURNAL_ID);
    }

    // Public setters

    /**
     * {@inheritDoc}
     * Transaction/Statement/* is internal code organization for exposing transaction.
     * Exposed APIs and entity names etc are 'transaction' only.
     */
    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = 'transaction';
    }

    /***
     * Public id for ledger statement is Journal Id
     *
     * @param array $array
     */
    public function setPublicIdAttribute(array & $array)
    {
        $array[static::ID] = static::$sign . static::getDelimiter() . $this->getAttribute(self::JOURNAL_ID);
    }

    // Appends
    public function getAccountNumberAttribute()
    {
        return $this->accountBalance->getAccountNumber();
    }

    public function getCreditAttribute()
    {
        if ($this->getAttribute(self::TYPE) == self::CREDIT)
        {
            return $this->getAttribute(self::AMOUNT);
        }

        return 0;
    }

    public function getDebitAttribute()
    {
        if ($this->getAttribute(self::TYPE) == self::DEBIT)
        {
            return $this->getAttribute(self::AMOUNT);
        }

        return 0;
    }

    public function isBalanceTypeBanking(): bool
    {
        return (optional($this->accountBalance)->getType() === Balance\Type::BANKING);
    }

    /**
     * {@inheritDoc}
     */
    public function toArrayPublic()
    {
        return PublicEntity::toArrayPublic();
    }

    public function getCreatedAtAttribute()
    {
        $createdAt = (int) $this->attributes[self::CREATED_AT];

        $app = App::getFacadeRoot();

        try
        {
            $app['trace']->info(
                TraceCode::LEDGER_STATEMENT_BALANCE_ENTITY,
                [
                    'balance_id' => $this->accountBalance->getId(),
                    'balance_type' => $this->accountBalance->getType(),
                    'balance_account_type' => $this->accountBalance->getAccountType(),
                ]);

            if ($this->isBalanceAccountTypeDirect() === true)
            {
                $postedAt = (int) $this->attributes[self::POSTED_AT];

                return $postedAt ? $postedAt : $createdAt;
            }
        }
        catch (\Throwable $e)
        {
            $app['trace']->traceException($e, Logger::ERROR, TraceCode::LEDGER_STATEMENT_CREATED_AT_ERROR);
        }

         return $createdAt;
    }


    public function isBalanceAccountTypeDirect(): bool
    {
        if ($this->isBalanceTypeBanking() === true)
        {
            return ($this->accountBalance->isAccountTypeDirect() === true);
        }

        return false;
    }

    /**
     * Sets public attributes of source relation.
     *
     * @param $array
     *
     */
    public function setPublicSourceAttribute(array & $array)
    {
        //
        // The assumption is that if source is required to be sent in the response,
        // it would have been already loaded. If it's not already loaded, we don't
        // want to do it as part of public setters.
        //
        if (isset($array[self::SOURCE]) === false)
        {
            return;
        }

        switch ($this->getTransactorType())
        {
            case E::PAYOUT:
                $this->setPublicSourceAttributeForPayout($array);
                break;

            case E::BANK_TRANSFER:
                $this->setPublicSourceAttributeForBankTransfer($array);
                break;

            case E::REVERSAL:
                // Do nothing special for reversal transactions
                break;

            case E::ADJUSTMENT:
                $this->setPublicSourceAttributeForAdjustment($array);
                break;

            case E::CREDIT_TRANSFER:
                $this->setPublicSourceAttributeForCreditTransfer($array);
                break;

            case E::EXTERNAL:
                $this->setPublicSourceAttributeForExternal($array);
                break;

            case E::FUND_ACCOUNT_VALIDATION:
                // Do nothing special for fund account validations
                break;

            default:
                // By default do not expose any source attributes
                $array[self::SOURCE] = [];
                break;
        }
    }

    protected function setPublicSourceAttributeForAdjustment(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                Adjustment\Entity::ID,
                Adjustment\Entity::ENTITY,
                Adjustment\Entity::DESCRIPTION,
                Adjustment\Entity::AMOUNT,
                Adjustment\Entity::CREATED_AT,
            ]);

        // Returning only absolute amount regardless of credit/debit
        $array['source']['amount'] = abs($array['source']['amount']);
    }

    public function setPublicSourceAttributeForCreditTransfer(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                CreditTransfer\Entity::ID,
                CreditTransfer\Entity::ENTITY,
                CreditTransfer\Entity::AMOUNT,
                CreditTransfer\Entity::STATUS,
                CreditTransfer\Entity::DESCRIPTION,
                CreditTransfer\Entity::UTR,
                CreditTransfer\Entity::MODE,
                CreditTransfer\Entity::PROCESSED_AT,
                CreditTransfer\Entity::PAYER_NAME,
                CreditTransfer\Entity::PAYER_ACCOUNT,
                CreditTransfer\Entity::PAYER_IFSC,
            ]);
    }

    protected function setPublicSourceAttributeForPayout(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                Payout\Entity::ID,
                Payout\Entity::ENTITY,
                Payout\Entity::FUND_ACCOUNT_ID,
                Payout\Entity::FUND_ACCOUNT,
                Payout\Entity::REVERSAL,
                Payout\Entity::STATUS,
                Payout\Entity::MODE,
                Payout\Entity::AMOUNT,
                Payout\Entity::FEES,
                Payout\Entity::TAX,
                Payout\Entity::UTR,
                Payout\Entity::NOTES,
                Payout\Entity::FEE_TYPE,
                Payout\Entity::CREATED_AT,
            ]);
    }

    protected function setPublicSourceAttributeForExternal(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                External\Entity::ID,
                External\Entity::ENTITY,
                External\Entity::AMOUNT,
                External\Entity::UTR,
            ]);
    }

    protected function setPublicSourceAttributeForBankTransfer(array & $array)
    {
        $array[self::SOURCE] = array_only(
            $array[self::SOURCE],
            [
                BankTransfer\Entity::ENTITY,
                BankTransfer\Entity::MODE,
                BankTransfer\Entity::BANK_REFERENCE,
                BankTransfer\Entity::AMOUNT,
                BankTransfer\Entity::PAYER_BANK_ACCOUNT,
                BankTransfer\Entity::PAYEE_ACCOUNT,
                BankTransfer\Entity::CREATED_AT,
            ]);

        /** @var BankTransfer\Entity $bankTransfer */
        $bankTransfer = $this->source;

        // Prepends id & entity as they are not exposed in bank_transfer entity, for now.
        $array[self::SOURCE] = [
                BankTransfer\Entity::ID             => $bankTransfer->getPublicId(),
                BankTransfer\Entity::ENTITY         => $bankTransfer->getEntity(),
                BankTransfer\Entity::PAYER_NAME     => $bankTransfer->getPayerName(),
                BankTransfer\Entity::PAYER_ACCOUNT  => $bankTransfer->getPayerAccount(),
                BankTransfer\Entity::PAYER_IFSC     => $bankTransfer->getPayerIfsc(),
            ] + $array[self::SOURCE];
    }

}
