<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Base\Traits\NotesTrait;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\Transaction\Entity as Transaction;

/**
 * @property FundAccount fundAccount
 * @property mixed merchant
 */
class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const ID                     = 'id';
    const RECEIPT                = 'receipt';
    const MERCHANT_ID            = 'merchant_id';
    const FUND_ACCOUNT_ID        = 'fund_account_id';
    // Fund Account Type is added just for faster filtering
    const FUND_ACCOUNT_TYPE      = 'fund_account_type';
    const STATUS                 = 'status';
    const ACCOUNT_STATUS         = 'account_status';
    const REGISTERED_NAME        = 'registered_name';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const ERROR_CODE             = 'error_code';
    const INTERNAL_ERROR_CODE    = 'internal_error_code';
    const ERROR_DESCRIPTION      = 'error_description';
    const NOTES                  = 'notes';
    const RESULTS                = 'results';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';

    // Key for the response
    const FUND_ACCOUNT          = 'fund_account';

    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    const PUBLIC_ENTITY_NAME = 'fund_account.validation';

    protected static $sign = 'fav';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::RECEIPT,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::FUND_ACCOUNT_ID,
        self::STATUS,
        self::FEES,
        self::TAX,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::FUND_ACCOUNT,
        self::STATUS,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::RESULTS,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::RESULTS,
    ];

    protected $defaults = [
        self::STATUS          => Status::CREATED,
        self::NOTES           => [],
        self::AMOUNT          => null,
        self::FEES            => null,
        self::TAX             => null,
        self::CURRENCY        => null,
        self::ACCOUNT_STATUS  => null,
        self::REGISTERED_NAME => null,
    ];

    protected $casts = [
        self::AMOUNT => 'int',
        self::FEES   => 'int',
        self::TAX    => 'int',
    ];

    protected $amounts = [
        self::AMOUNT,
        self::FEES,
        self::TAX,
    ];

    // -------------- Relations --------------

    public function fundAccount()
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'source', 'type', 'entity_id');
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    // -------------- Setters --------------

    public function setAmount(int $amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setCurrency(string $currency)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setTax(int $tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFees(int $fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function associateFundAccount(FundAccount $fundAccount)
    {
        $this->fundAccount()->associate($fundAccount);

        $this->setFundAccountType($fundAccount->getAccountType());
    }

    protected function setFundAccountType(string $type)
    {
        $this->setAttribute(self::FUND_ACCOUNT_TYPE, $type);
    }

    public function setStatus(string $status = null)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    public function setAccountStatus(string $status = null)
    {
        return $this->setAttribute(self::ACCOUNT_STATUS, $status);
    }

    public function setRegisteredName(string $name = null)
    {
        return $this->setAttribute(self::REGISTERED_NAME, $name);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    // -------------- Public Setters --------------

    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = self::PUBLIC_ENTITY_NAME;
    }

    public function setPublicResultsAttribute(array & $array)
    {
        $array[self::RESULTS] = [
            self::ACCOUNT_STATUS  => $this->getAccountStatus(),
            self::REGISTERED_NAME => $this->getRegisteredName(),
        ];
    }

    // -------------- Getters --------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getTax()
    {
        return $this->getAttribute(self::TAX);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getAccountStatus()
    {
        return $this->getAttribute(self::ACCOUNT_STATUS);
    }

    public function getRegisteredName()
    {
        return $this->getAttribute(self::REGISTERED_NAME);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getFundAccountType()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_TYPE);
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    // ------------ Mocked Setters ---------

    public function setUtr(string $value = null)
    {
        return;
    }

    public function setRemarks(string $value = null)
    {
        return;
    }

    // ------------ Mocked Getters ---------

    public function getBaseAmount()
    {
        return $this->getAmount();
    }

    public function getPricingFeatures()
    {
        return [];
    }

    public function getMethod()
    {
        return $this->getAttribute(self::FUND_ACCOUNT_TYPE);
    }

    public function isStatusFailed()
    {
        return ($this->getAccountStatus() === AccountStatus::INVALID);
    }
}
