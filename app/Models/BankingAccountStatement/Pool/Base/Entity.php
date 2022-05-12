<?php

namespace RZP\Models\BankingAccountStatement\Pool\Base;

use Database\Connection;

use RZP\Models\Base;
use RZP\Base\BuilderEx;
use RZP\Models\Merchant;
use RZP\Models\Currency\Currency;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\BankingAccountStatement\Type;
use RZP\Models\BankingAccountStatement\Category;

/**
 * @property Merchant\Entity     $merchant
 */

class Entity extends Base\PublicEntity
{
    // Ideally this should be there but this gives issues in running unit testcases.
    //protected $connection = Connection::RX_ACCOUNT_STATEMENTS_LIVE;

    const CHANNEL               = 'channel';
    const MERCHANT_ID           = 'merchant_id';
    const ACCOUNT_NUMBER        = 'account_number';
    const BANK_TRANSACTION_ID   = 'bank_transaction_id';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const TYPE                  = 'type';
    const DESCRIPTION           = 'description';
    const CATEGORY              = 'category';
    /**
     * Generated at bank side, Denotes the order in which a
     * particular transaction has occurred at a given point in time
     */
    const BANK_SERIAL_NUMBER    = 'bank_serial_number';
    /**
     * This is the populated only in cases of Cheques and Demand Drafts
     */
    const BANK_INSTRUMENT_ID    = 'bank_instrument_id';
    const BALANCE               = 'balance';
    const BALANCE_CURRENCY      = 'balance_currency';
    const POSTED_DATE           = 'posted_date';
    const TRANSACTION_DATE      = 'transaction_date';
    const UTR                   = 'utr';
    const ENTITY_ID             = 'entity_id';

    const CREDIT_REGEX = '/^(RTGS\/|NEFT\/|UPI\/|R\/UPI\/|R-)(.*?)(\/|-)/';

    // sample IMPS - 010617021414-QCREDIT 234412
    const IMPS_DEBIT_REGEX = '/^(.*?)-/';

    // sample NEFT - NEFT/000119662132/maYANK SHARMA
    // sample RTGS - RTGS/UTIBH20106341692/RAZORPAY SOFTWARE PRIVATE LI
    const NEFT_RTGS_DEBIT_REGEX = '/^(RTGS\/|NEFT\/)(.*?)(\/)/';

    // sample UPI- UPI/120310176379/Test transfer RAZORPAY/razorpayx.
    const UPI_DEBIT_REGEX = '/^(UPI\/)(.*?)(\/)/';

    protected static $sign = 'bas';

    protected $fillable = [
        self::ACCOUNT_NUMBER,
        self::BANK_TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TYPE,
        self::DESCRIPTION,
        self::CATEGORY,
        self::BANK_SERIAL_NUMBER,
        self::BANK_INSTRUMENT_ID,
        self::BALANCE,
        self::BALANCE_CURRENCY,
        self::POSTED_DATE,
        self::TRANSACTION_DATE,
        self::UTR,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::ACCOUNT_NUMBER,
        self::BANK_TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TYPE,
        self::DESCRIPTION,
        self::CATEGORY,
        self::BANK_SERIAL_NUMBER,
        self::BANK_INSTRUMENT_ID,
        self::BALANCE,
        self::BALANCE_CURRENCY,
        self::POSTED_DATE,
        self::TRANSACTION_DATE,
        self::UTR,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ACCOUNT_NUMBER,
        self::BANK_TRANSACTION_ID,
        self::DESCRIPTION,
        self::UTR,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::BALANCE           => 'int',
        self::POSTED_DATE       => 'int',
        self::TRANSACTION_DATE  => 'int',
    ];

    protected $defaults = [
        self::CURRENCY          => Currency::INR,
        self::BALANCE_CURRENCY  => Currency::INR,
    ];

    protected static $generators = [
        self::ID,
    ];

    // --------------------------- Relations ---------------------------------- //

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    // ---------------------------- Setters ----------------------------------- //

    public function setAccountNumber(string $accountNumber)
    {
        $this->setAttribute(self::ACCOUNT_NUMBER, $accountNumber);
    }

    public function setBankTransactionId(string $banktransactionId)
    {
        $this->setAttribute(self::BANK_TRANSACTION_ID, $banktransactionId);
    }

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency(string $currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setType(string $type)
    {
        Type::validate($type);

        $this->setAttribute(self::TYPE, $type);
    }

    public function setDescription(string $description)
    {
        $this->setAttribute(self::DESCRIPTION, $description);
    }

    public function setCategory(string $category)
    {
        Category::validate($category);

        $this->setAttribute(self::CATEGORY, $category);
    }

    public function setSerialNumber(string $serialNumber)
    {
        $this->setAttribute(self::BANK_SERIAL_NUMBER, $serialNumber);
    }

    public function setInstrumentId(string $instrumentId)
    {
        $this->setAttribute(self::BANK_INSTRUMENT_ID, $instrumentId);
    }

    public function setBalance(int $balance)
    {
        $this->setAttribute(self::BALANCE, $balance);
    }

    public function setBalanceCurrency(string $currency)
    {
        $this->setAttribute(self::BALANCE_CURRENCY, $currency);
    }

    public function setPostedDate($date)
    {
        $this->setAttribute(self::POSTED_DATE, $date);
    }

    public function setTransactionDate($date)
    {
        $this->setAttribute(self::TRANSACTION_DATE, $date);
    }

    public function setUtr($utr = null)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    // -------------------------- Getters ------------------------------------ //

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getPostedDate()
    {
        return $this->getAttribute(self::POSTED_DATE);
    }

    public function getTransactionDate()
    {
        return $this->getAttribute(self::TRANSACTION_DATE);
    }

    public function getBankTransactionId()
    {
        return $this->getAttribute(self::BANK_TRANSACTION_ID);
    }

    public function getBankInstrumentId()
    {
        return $this->getAttribute(self::BANK_INSTRUMENT_ID);
    }

    public function getSerialNumber()
    {
        return $this->getAttribute(self::BANK_SERIAL_NUMBER);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
    }

    public function getBalance()
    {
        return $this->getAttribute(self::BALANCE);
    }

    public function getAccountNumber()
    {
        return $this->getAttribute(self::ACCOUNT_NUMBER);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getCategory()
    {
        return $this->getAttribute(self::CATEGORY);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function isTypeCredit()
    {
        return ($this->getType() === Type::CREDIT);
    }

    public function isTypeDebit()
    {
        return ($this->getType() === Type::DEBIT);
    }

    /**
     * @param BuilderEx $query
     * @param array $columns
     * @param array $values
     * @return BuilderEx
     * Sample query - https://github.com/razorpay/api/pull/21802#issuecomment-812577026
     */
    public static function scopeWhereInMultiple(BuilderEx $query, array $columns, array $values)
    {
        collect($values)
            ->transform(function ($v) use ($columns) {
                $clause = [];
                foreach ($columns as $index => $column) {
                    $clause[] = [$column, '=', $v[$index]];
                }
                return $clause;
            })->each(function($clause, $index) use ($query) {
                $query->where($clause, null, null,  'or');
            });

        return $query;
    }
}
