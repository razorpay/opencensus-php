<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Contact;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
use RZP\Models\Reversal;
use RZP\Models\Base\Traits;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;
use RZP\Models\Feature\Constants as MerchantFeature;


/**
 * @property FundAccount fundAccount
 * @property mixed merchant
 * @property Reversal\Entity reversal
 *
 */
class Entity extends Base\PublicEntity
{
    use Traits\NotesTrait, AsvGetAttribute;
    use Traits\HasBalance;

    protected $isCompositeResponse = false;

    const ID                     = 'id';
    const RECEIPT                = 'receipt';
    const MERCHANT_ID            = 'merchant_id';
    const FUND_ACCOUNT_ID        = 'fund_account_id';
    // Fund Account Type is added just for faster filtering
    const FUND_ACCOUNT_TYPE      = 'fund_account_type';
    const STATUS                 = 'status';
    const ACCOUNT_STATUS         = 'account_status';
    const REGISTERED_NAME        = 'registered_name';
    const UTR                    = 'utr';
    const TRANSACTION_ID         = 'transaction_id';
    const FEES                   = 'fees';
    const TAX                    = 'tax';
    const AMOUNT                 = 'amount';
    const CURRENCY               = 'currency';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const BALANCE_ID             = 'balance_id';
    const ERROR_CODE             = 'error_code';
    const INTERNAL_ERROR_CODE    = 'internal_error_code';
    const ERROR_DESCRIPTION      = 'error_description';
    const NOTES                  = 'notes';
    const RESULTS                = 'results';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';
    const RETRY_AT               = 'retry_at';
    const ATTEMPTS               = 'attempts';

    // Key for the response
    const FUND_ACCOUNT          = 'fund_account';
    const VALIDATION_RESULTS    = 'validation_results';
    const DETAILS               = 'details';
    const STATUS_DETAILS        = 'status_details';


    const CONTACT               = 'contact';

    // Key for retry API
    const FUND_ACCOUNT_VALIDATION_IDS   = 'fund_account_validation_ids';

    const VALIDATION_TYPE        = 'validation_type';

    const SOURCE_ACCOUNT_NUMBER  = 'source_account_number';

    const REFERENCE_ID           = 'reference_id';

    const ACCOUNT_TYPE           = 'account_type';

    const ERROR                  = 'error';

    const IS_COMPOSITE           = 'isComposite';

    const PRICING_RULE_ID        = 'pricing_rule_id';

    const NAME_MATCH_SCORE       = 'name_match_score';
    const DESCRIPTION            = 'description';
    const SOURCE                 = 'source';
    const REASON                 = 'reason';

//    TODO: add it in fillable and visible
    const IS_CREATED_USING_FAV_SERVICE = 'IS_CREATED_USING_FAV_SERVICE';

    protected $entity = Constants\Entity::FUND_ACCOUNT_VALIDATION;

    public $favServiceResponse;

    const PUBLIC_ENTITY_NAME = 'fund_account.validation';

    // Any changes to this sign will affect LedgerStatus Job as well
    protected static $sign = 'fav';

    protected $generateIdOnCreate = true;

    /*
    This flag is set to true when ledger response is awaited due to some failure on ledger
    In such cases this can be utilized to skip fts calls which will be handled later when ledger
    status is checked in async
    */
    protected $ledgerResponseAwaitedFlag = false;

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
        self::ATTEMPTS,
        self::RETRY_AT,
        self::FEES,
        self::TAX,
        self::TRANSACTION_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::NOTES,
        self::BALANCE_ID,
        self::ERROR_CODE,
        self::INTERNAL_ERROR_CODE,
        self::ERROR_DESCRIPTION,
        self::CREATED_AT,
        self::UTR,
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
        self::UTR,
        self::VALIDATION_RESULTS,
        self::STATUS_DETAILS,
        self::CONTACT
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::RESULTS,
        self::FUND_ACCOUNT,
        self::VALIDATION_RESULTS,
        self::STATUS_DETAILS,
        self::AMOUNT,
        self::CURRENCY,
        self::UTR,
        self::CONTACT
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
        self::UTR             => null,
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

    public function setIsCreatedUsingFavService(int $isCreatedUsingFavService = 0)
    {
        $this->setAttribute(self::IS_CREATED_USING_FAV_SERVICE, $isCreatedUsingFavService);
    }

    public function setAmount(int $amount = null)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setContact(Contact\Entity $contact = null)
    {
        $this->setAttribute(self::CONTACT, $contact);
    }

    public function setledgerResponseAwaitedFlag(bool $flag)
    {
        $this->ledgerResponseAwaitedFlag = $flag;
        return $this;
    }

    public function setCurrency(string $currency = null)
    {
        $this->setAttribute(self::CURRENCY, $currency);
    }

    public function setNameMatchScore(string $nameMatchScore = null)
    {
        $this->setAttribute(self::NAME_MATCH_SCORE, $nameMatchScore);
    }

    public function setSource(string $source = null)
    {
        $this->setAttribute(self::SOURCE, $source);
    }

    public function setDescription(string $desc = null)
    {
        $this->setAttribute(self::DESCRIPTION, $desc);
    }

    public function setErrorCode(string $errorCode = null)
    {
        $this->setAttribute(self::ERROR_CODE, $errorCode);
    }

    public function setInternalErrorCode(string $errorCode = null)
    {
        $this->setAttribute(self::INTERNAL_ERROR_CODE, $errorCode);
    }

    public function setReason(string $reason = null)
    {
        $this->setAttribute(self::REASON, $reason);
    }

    public function setDetails(string $details = null)
    {
        $this->setAttribute(self::DETAILS, $details);
    }

    public function setTax(int $tax)
    {
        $this->setAttribute(self::TAX, $tax);
    }

    public function setFees(int $fees)
    {
        $this->setAttribute(self::FEES, $fees);
    }

    public function setTransactionId(string $transactionId)
    {
        $this->setAttribute(self::TRANSACTION_ID, $transactionId);
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
        $previousStatus = $this->getStatus();

        $this->setAttribute(self::STATUS, $status);

        Metric::pushStatusChangeMetrics($this, $previousStatus);

    }

    public function setAttempts(int $attempts)
    {
        return $this->setAttribute(self::ATTEMPTS, $attempts);
    }

    public function setErrorDescription(string $errorDesc = null)
    {
        return $this->setAttribute(self::ERROR_DESCRIPTION, $errorDesc);
    }

    public function setRetryAt($retryAt)
    {
        $this->setAttribute(self::RETRY_AT, $retryAt);
    }

    public function setAccountStatus(string $status = null)
    {
        return $this->setAttribute(self::ACCOUNT_STATUS, $status);
    }

    public function setRegisteredName(string $name = null)
    {
        return $this->setAttribute(self::REGISTERED_NAME, $name);
    }

    public function setUtr(string $value = null)
    {
        return $this->setAttribute(self::UTR, $value);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function setIsCompositeResponse($flag)
    {
        $this->isCompositeResponse = $flag;
    }

    // -------------- Public Setters --------------

    public function setPublicEntityAttribute(array & $array)
    {
        $array[self::ENTITY] = self::PUBLIC_ENTITY_NAME;
    }

    public function setPublicContactAttribute(array & $array)
    {
        if(($this->isCompositeNewResponseRequired() === true) and ($this->getContact() != null))
        {
            $array[self::FUND_ACCOUNT][self::CONTACT] = $this->getContact()->toArrayPublic();
        }
        else
        {
            unset($array[self::CONTACT]);
        }
    }

    public function setPublicResultsAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            unset($array[self::RESULTS]);
        }

        else
        {
            $array[self::RESULTS] = [
                self::ACCOUNT_STATUS  => $this->getAccountStatus(),
                self::REGISTERED_NAME => $this->getRegisteredName(),
            ];

            $merchant = $this->merchant;

            if ( ($merchant !== null) and
                ($merchant->isFeatureEnabled(MerchantFeature::EXPOSE_FA_VALIDATION_UTR) === true))
            {
                $array[self::RESULTS][self::UTR] = $this->getUtr();
            }
        }
    }

    public function setPublicUtrAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            unset($array[self::UTR]);
        }
    }

    public function setPublicAmountAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            unset($array[self::AMOUNT]);
        }
    }

    public function setPublicCurrencyAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            unset($array[self::CURRENCY]);
        }
    }

    public function setPublicValidationResultsAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            $array[self::VALIDATION_RESULTS] = [
                self::ACCOUNT_STATUS   => $this->getAccountStatus(),
                self::REGISTERED_NAME  => $this->getRegisteredName(),
                self::NAME_MATCH_SCORE => $this->getNameMatchScore(),
                self::DETAILS          => $this->getDetails()
            ];
        }
        else
        {
            unset($array[self::VALIDATION_RESULTS]);
        }
    }

    public function setPublicStatusDetailsAttribute(array & $array)
    {
        if($this->isCompositeNewResponseRequired() === true)
        {
            $array[self::STATUS_DETAILS] = [
                'description'  => $this->getDescription(),
                'source'       => $this->getSource(),
                'reason'       => $this->getReason(),
            ];
        }
        else
        {
            unset($array[self::STATUS_DETAILS]);
        }
    }

    public function setPublicFundAccountAttribute(array & $favEntity)
    {
        $bankAccount    = FundAccountEntity::BANK_ACCOUNT;
        $vpa            = FundAccountEntity::VPA;
        $details        = FundAccountEntity::DETAILS;
        $contactID        = FundAccountEntity::CONTACT_ID;

        if ((isset($favEntity[self::FUND_ACCOUNT][$bankAccount]) === true) and
            ($this->isCompositeNewResponseRequired() === false))
        {
            $favEntity[self::FUND_ACCOUNT][$details] = $favEntity[self::FUND_ACCOUNT][$bankAccount];
        }

        if (isset($favEntity[self::FUND_ACCOUNT][$vpa]) === true  and
            ($this->isCompositeNewResponseRequired() === false))
        {
            $favEntity[self::FUND_ACCOUNT][$details] = $favEntity[self::FUND_ACCOUNT][$vpa];
        }
    }



    // -------------- Getters --------------

    public function isCreatedUsingFavService()
    {
        return $this->getAttribute(self::IS_CREATED_USING_FAV_SERVICE);
    }

    public function getBalanceId()
    {
        return $this->getAttribute(self::BALANCE_ID);
    }

    public function getLedgerResponseAwaitedFlag()
    {
        return $this->ledgerResponseAwaitedFlag;
    }

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

    public function getAttempts(): int
    {
        return $this->getAttribute(self::ATTEMPTS);
    }

    public function getFees()
    {
        return $this->getAttribute(self::FEES);
    }

    public function getFee()
    {
        return $this->getFees();
    }

    public function getAccountStatus()
    {
        return $this->getAttribute(self::ACCOUNT_STATUS);
    }

    public function getContact()
    {
        return $this->getAttribute(self::CONTACT);
    }

    public function getRegisteredName()
    {
        return $this->getAttribute(self::REGISTERED_NAME);
    }

    public function getNameMatchScore()
    {
        return $this->getAttribute(self::NAME_MATCH_SCORE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getSource()
    {
        return $this->getAttribute(self::SOURCE);
    }

    public function getErrorCode()
    {
        return $this->getAttribute(self::ERROR_CODE);
    }

    public function getReason()
    {
        return $this->getAttribute(self::REASON);
    }

    public function getDetails()
    {
        return $this->getAttribute(self::DETAILS);
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

    // This is deprecated, Please use RZP\Models\Transaction\Ledger -> findByIdFromLedger
    public function getTransactionId()
    {
        $transaction = $this->transaction;

        return optional($transaction)->getId();
    }

    public function getReceipt()
    {
        return $this->getAttribute(self::RECEIPT);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getTransactionIdViaAttribute()
    {
        $this->getAttribute(self::TRANSACTION_ID);
    }

    // ------------ Mocked Setters ---------

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

    // ------------- Relations ---------------

    public function reversal()
    {
        return $this->belongsTo(Reversal\Entity::class, self::ID, Reversal\Entity::ENTITY_ID);
    }

    public function isCompositeNewResponseRequired(): bool
    {
        return ($this->isCompositeResponse === true);
    }

}
