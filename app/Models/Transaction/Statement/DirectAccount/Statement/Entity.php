<?php

namespace RZP\Models\Transaction\Statement\DirectAccount\Statement;

use RZP\Models\Payout;
use RZP\Models\External;
use RZP\Models\BankingAccount;
use RZP\Constants\Entity as E;
use RZP\Models\Merchant\Balance;
use RZP\Models\Transaction as Txn;
use RZP\Models\BankingAccountStatement;

/**
 * Class Entity
 *
 * @package RZP\Models\Transaction\Statement\DirectAccount\Statement
 */
class Entity extends BankingAccountStatement\Entity
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

    const ACTION = 'action';

    //Used Exclusively for the ES raw searching of Email
    const CONTACT_EMAIL_RAW = 'contact_email.raw';

    const ACCOUNT_BALANCE   = 'account_balance';

    const NOTES           = 'notes';

    //ES Fund account number search
    const FUND_ACCOUNT_NUMBER  = 'fund_account_number';

    //Partial Search
    const CONTACT_PHONE_PS  = 'contact_phone_ps';
    const CONTACT_EMAIL_PS  = 'contact_email_ps';


    //Used Exclusively for the ES raw searching of Email
    const CONTACT_EMAIL_PARTIAL_SEARCH = 'contact_email.partial_search';

    protected $entity = 'direct_account_statement';

    protected $primaryKey = self::ID;

    protected $public = [
        self::ID,
        self::ENTITY,
        self::ACCOUNT_NUMBER,
        self::AMOUNT,
        self::CURRENCY,
        self::TYPE,
        self::CREDIT,
        self::DEBIT,
        self::BALANCE,
        self::TRANSACTION_ID,
        self::CREATED_AT,
        self::SOURCE,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::TRANSACTION_ID,
        self::TYPE,
    ];

    protected $amounts = [
        self::DEBIT,
        self::CREDIT,
    ];

    protected $casts = [
        self::CREDIT  => 'int',
        self::DEBIT   => 'int',
        self::AMOUNT  => 'int',
        self::BALANCE => 'int',
    ];

    protected $appends = [
        self::CREDIT,
        self::DEBIT,
    ];

    public function accountBalance()
    {
        return $this->belongsTo(Balance\Entity::class, Entity::ACCOUNT_NUMBER, Balance\Entity::ACCOUNT_NUMBER);
    }

    // Public setters

    /**
     * Transaction/Statement/* is internal code organization for exposing transaction.
     * Exposed APIs and entity names etc are 'transaction' only.
     */
    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = 'transaction';
    }

    // For now, the frontend relies on receiving the txn entity ID in the 'id' key in the response
    // This function exchanges the txn ID and the base BAS entity ID
    // This will help in the frontend when a merchant tries to click on a single record in the Acc Stmt page.
    // As, that operation calls the fetch single txn (GET /transactions/{id}) API.
    // Also, in the response, letting the 'transaction_id' key contain the 'bas_' prefix in its value, as
    // 'transaction_id' hey in the response array is never consumed by the frontend
    // TODO: Change this before taking frontend changes live.
    public function setPublicTransactionIdAttribute(array &$array)
    {
        $txnId = empty($array[self::TRANSACTION_ID]) ?
            null :
            Txn\Entity::getSign() . Txn\Entity::getDelimiter() . $array[self::TRANSACTION_ID];

        if (empty($txnId) === false)
        {
            $array[self::ID] = $txnId;
            unset($array[self::TRANSACTION_ID]);
        }
    }

    public function setPublicTypeAttribute(array &$array)
    {
        if ($array[self::TYPE] === self::CREDIT)
        {
            $array[self::CREDIT] = $array[self::AMOUNT];
            $array[self::DEBIT] = 0;
        }

        if ($array[self::TYPE] === self::DEBIT)
        {
            $array[self::DEBIT] = $array[self::AMOUNT];
            $array[self::CREDIT] = 0;
        }

        unset($array[self::TYPE]);
    }

    public function getCreditAttribute()
    {
        if ($this->isTypeCredit() === self::CREDIT)
        {
            return $this->getAttribute(self::AMOUNT);
        }

        return 0;
    }

    public function getDebitAttribute()
    {
        if ($this->isTypeDebit() === self::DEBIT)
        {
            return $this->getAttribute(self::AMOUNT);
        }

        return 0;
    }

    public function isBalanceTypeBanking(): bool
    {
        return (optional($this->accountBalance)->getType() === Balance\Type::BANKING);
    }

    // Is this even needed if the only thing now this does is type casting?
    // Maybe use entity's $casts property later.
    public function getCreatedAtAttribute()
    {
        $createdAt = (int) $this->attributes[self::CREATED_AT];

        if ($this->isBalanceAccountTypeDirect() === true)
        {
            $postedAt = (int) $this->attributes[self::POSTED_DATE];

            return $postedAt ? $postedAt : $createdAt;
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

        switch ($this->getEntityType())
        {
            case E::PAYOUT:
                $this->setPublicSourceAttributeForPayout($array);
                break;

            case E::REVERSAL:
                // Do nothing special for reversal transactions
                break;

            case E::EXTERNAL:
                $this->setPublicSourceAttributeForExternal($array);
                break;

            default:
                // By default do not expose any source attributes
                $array[self::SOURCE] = [];
                break;
        }
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
}
