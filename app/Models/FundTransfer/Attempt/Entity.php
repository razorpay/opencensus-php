<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Card\Issuer;
use RZP\Constants\Entity as E;
use RZP\Models\FundTransfer\Mode;
use RZP\Models\Settlement\Channel;
use RZP\Models\FundTransfer\Yesbank\NodalAccount;

/**
 * @property mixed batchFundTransfer
 * @property mixed bankAccount
 * @property Card\Entity $card
 */
class Entity extends Base\PublicEntity
{
    const SOURCE                 = 'source';
    const SOURCE_TYPE            = 'source_type';
    const SOURCE_ID              = 'source_id';
    const MERCHANT_ID            = 'merchant_id';
    const PURPOSE                = 'purpose';
    const BANK_ACCOUNT_ID        = 'bank_account_id';
    const VPA_ID                 = 'vpa_id';
    const CARD_ID                = 'card_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const CHANNEL                = 'channel';
    const VERSION                = 'version';
    const BANK_STATUS_CODE       = 'bank_status_code';
    const BANK_RESPONSE_CODE     = 'bank_response_code';
    const MODE                   = 'mode';
    const STATUS                 = 'status';
    const UTR                    = 'utr';
    const NARRATION              = 'narration';
    const REMARKS                = 'remarks';
    const DATE_TIME              = 'date_time';
    const CMS_REF_NO             = 'cms_ref_no';
    const FAILURE_REASON         = 'failure_reason';
    const TXT_FILE_ID            = 'txt_file_id';
    const EXCEL_FILE_ID          = 'excel_file_id';
    const INITIATE_AT            = 'initiate_at';
    const FTS_TRANSFER_ID        = 'fts_transfer_id';

    //Fund transfer retry constants
    const FILE                  = 'file';
    const FILE_TYPE             = 'file_type';
    const SETTLEMENT            = 'settlement';
    const BENEFICIARY           = 'beneficiary';

    /**
     * Used to check if the FTA's source has balance ID
     */
    const BALANCE_ID            = 'balance_id';

    /**
     * used to identify if the transfer is done through FTS
     */
    const IS_FTS                 = 'is_fts';

    /**
     * used to map the FTA with transfer id of FTS
     */
    const FUND_TRANSFER_ID      = 'fund_transfer_id';

    protected $entity = 'fund_transfer_attempt';

    protected $fillable = [
        self::PURPOSE,
        self::CHANNEL,
        self::VERSION,
        self::STATUS,
        self::MODE,
        self::IS_FTS,
        self::NARRATION,
        self::BANK_STATUS_CODE,
        self::BANK_RESPONSE_CODE,
        self::STATUS,
        self::REMARKS,
        self::FAILURE_REASON,
        self::INITIATE_AT,
        self::DATE_TIME,
    ];

    protected $visible = [
        self::ID,
        self::SOURCE,
        self::MERCHANT_ID,
        self::PURPOSE,
        self::BANK_ACCOUNT_ID,
        self::VPA_ID,
        self::CARD_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::CHANNEL,
        self::VERSION,
        self::BANK_STATUS_CODE,
        self::BANK_RESPONSE_CODE,
        self::MODE,
        self::STATUS,
        self::UTR,
        self::IS_FTS,
        self::NARRATION,
        self::REMARKS,
        self::DATE_TIME,
        self::CMS_REF_NO,
        self::FAILURE_REASON,
        self::TXT_FILE_ID,
        self::EXCEL_FILE_ID,
        self::INITIATE_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
        self::STATUS,
        self::UTR,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::SOURCE,
    ];

    protected $ignoredRelations = [
        'source',
    ];

    /**
     * Generate ID with all characters in upper-case
     * for ICICI, because their Recon file has the ID
     * in upper-case. If we do not create it this way,
     * when we query on this ID during reconciliation,
     * we'd need to do a case-insensitive search
     * which will do a full-table scan.
     * To avoid a case-insensitive search on the table,
     * we save the ID in upper-case.
     */
    public function generateId()
    {
        $id = static::generateUniqueId();

        $channel = $this->getAttribute(self::CHANNEL);

        if ($channel === Channel::ICICI)
        {
            $id = strtoupper($id);
        }

        $this->setAttribute(self::ID, $id);

        return $this;
    }

    public function source()
    {
        return $this->morphTo('source', self::SOURCE_TYPE, self::SOURCE_ID);
    }

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function bankAccount()
    {
        return $this->belongsTo('RZP\Models\BankAccount\Entity');
    }

    public function vpa()
    {
        return $this->belongsTo('RZP\Models\Vpa\Entity');
    }

    public function card()
    {
        return $this->belongsTo('RZP\Models\Card\Entity');
    }

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    // ------------------------------- getters ---------------------------------

    public function getVpaId()
    {
        return $this->getAttribute(self::VPA_ID);
    }

    public function getCardId()
    {
        return $this->getAttribute(self::CARD_ID);
    }

    public function getBankAccountId()
    {
        return $this->getAttribute(self::BANK_ACCOUNT_ID);
    }

    public function getChannel()
    {
        return $this->getAttribute(self::CHANNEL);
    }

    public function getPurpose()
    {
        return $this->getAttribute(self::PURPOSE);
    }

    public function getRemarks()
    {
        return $this->getAttribute(self::REMARKS);
    }

    public function getFailureReason()
    {
        return $this->getAttribute(self::FAILURE_REASON);
    }

    public function getNarration()
    {
        return $this->getAttribute(self::NARRATION);
    }

    public function getUtr()
    {
        return $this->getAttribute(self::UTR);
    }

    public function getVersion()
    {
        return $this->getAttribute(self::VERSION);
    }

    public function getBankStatusCode()
    {
        return $this->getAttribute(self::BANK_STATUS_CODE);
    }

    public function getBankResponseCode()
    {
        return $this->getAttribute(self::BANK_RESPONSE_CODE);
    }

    public function getSourceId()
    {
        return $this->getAttribute(self::SOURCE_ID);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function getSourceType()
    {
        return $this->getAttribute(self::SOURCE_TYPE);
    }

    public function getBatchFundTransferId()
    {
        return $this->getAttribute(self::BATCH_FUND_TRANSFER_ID);
    }

    public function getInitiateAt()
    {
        return $this->getAttribute(self::INITIATE_AT);
    }

    public function hasMode()
    {
        return ($this->isAttributeNotNull(self::MODE) === true);
    }

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function getCmsRefNo()
    {
        return $this->getAttribute(self::CMS_REF_NO);
    }

    public function getDateTime()
    {
        return $this->getAttribute(self::DATE_TIME);
    }

    public function isRefund()
    {
        return ($this->getAttribute(self::PURPOSE) === Purpose::REFUND);
    }

    public function isSettlement()
    {
        return ($this->getAttribute(self::PURPOSE) === Purpose::SETTLEMENT);
    }

    public function getDestinationType()
    {
        if ($this->hasVpa() === true)
        {
            return E::VPA;
        }
        else if ($this->hasBankAccount() === true)
        {
            return E::BANK_ACCOUNT;
        }
        else if ($this->hasCard() === true)
        {
            return E::CARD;
        }
        else
        {
            return null;
        }
    }

    public function getFTSTransferId()
    {
        return $this->getAttribute(self::FTS_TRANSFER_ID);
    }

    public function getIsFTS()
    {
        return (bool) $this->getAttribute(self::IS_FTS);
    }

    public function hasBankAccount()
    {
        return ($this->isAttributeNotNull(self::BANK_ACCOUNT_ID));
    }

    public function hasVpa()
    {
        return ($this->isAttributeNotNull(self::VPA_ID));
    }

    public function hasCard()
    {
        return ($this->isAttributeNotNull(self::CARD_ID));
    }

    // ------------------------------- setters ---------------------------------

    public function setRemarks($remarks)
    {
        $this->setAttribute(self::REMARKS, $remarks);
    }

    public function setUtr($utr)
    {
        $this->setAttribute(self::UTR, $utr);
    }

    public function setSourceType($type)
    {
        $this->setAttribute(self::SOURCE_TYPE, $type);
    }

    public function setSourceId($id)
    {
        $this->setAttribute(self::SOURCE_ID, $id);
    }

    public function setStatus($status)
    {
        $this->setAttribute(self::STATUS, $status);
    }

    public function setBankStatusCode($code)
    {
        $this->setAttribute(self::BANK_STATUS_CODE, $code);
    }

    public function setBankResponseCode($code)
    {
        $this->setAttribute(self::BANK_RESPONSE_CODE, $code);
    }

    public function setFailureReason($reason)
    {
        $this->setAttribute(self::FAILURE_REASON, $reason);
    }

    public function setCmsRefNo($refNo)
    {
        $this->setAttribute(self::CMS_REF_NO, $refNo);
    }

    public function setDateTime($dateTime)
    {
        $this->setAttribute(self::DATE_TIME, $dateTime);
    }

    public function setInitiateAt($initiateAt)
    {
        $this->setAttribute(self::INITIATE_AT, $initiateAt);
    }

    public function setFTSTransferId($ftsTransferId)
    {
        $this->setAttribute(self::FTS_TRANSFER_ID, $ftsTransferId);
    }

    public function setIsFTS(bool $isFTS)
    {
        $this->setAttribute(self::IS_FTS, $isFTS);
    }

    // ------------------------------ modifiers --------------------------------

    protected function setRemarksAttribute($remarks)
    {
        $this->attributes[self::REMARKS] = substr($remarks, 0, 255);
    }

    protected function setDateTimeAttribute($dateTime)
    {
        $this->attributes[self::DATE_TIME] = substr($dateTime, 0, 255);
    }

    protected function setCmsRefNoAttribute($refNo)
    {
        $this->attributes[self::CMS_REF_NO] = substr($refNo, 0, 255);
    }

    public function modifyModeIfRequired()
    {
        if ($this->hasMode() === false)
        {
            return;
        }

        // Assumption is that the validation would have happened already before this
        // step and hence we can assume that the bank account exists and is valid.

        $ba = $this->bankAccount;

        $ifsc = $ba->getIfscCode();

        $ifscFirstFour = substr($ifsc, 0, 4);

        if (starts_with($ifscFirstFour, NodalAccount::IFSC_IDENTIFIER) === true)
        {
            $this->setMode(Mode::IFT);
        }
    }

    // -------------------------------- methods --------------------------------

    public function isStatusCreated()
    {
        return ($this->getStatus() === Status::CREATED);
    }

    public function isPendingReconciliation()
    {
        return ($this->getStatus() === Status::PENDING_RECONCILIATION);
    }

    public function isStatusFailed()
    {
        return ($this->getStatus() === Status::FAILED);
    }

    /**
     * One attempt has one source
     * One source has many attempts, created incrementally
     *
     * @return boolean
     */
    public function isBatchSameAsSource(): bool
    {
        $ftaBatchId = $this->getBatchFundTransferId();

        $sourceBatchId  = $this->source->getBatchFundTransferId();

        if ($ftaBatchId === $sourceBatchId)
        {
            return true;
        }

        return false;
    }

    public function isOfBanking(): bool
    {
        $source = $this->source;

        if (($source->hasAttribute(self::BALANCE_ID) === true) and
            (method_exists($source, 'hasBalance') === true) and
            ($source->hasBalance() === true))
        {
            return $source->isBalanceTypeBanking();
        }

        return false;
    }

    public function isPennyTesting(): bool
    {
        return ($this->getSourceType() === Type::FUND_ACCOUNT_VALIDATION);
    }

    // ---------------------------- public setters -----------------------------

    public function setPublicSourceAttribute(array & $attributes)
    {
        $sourceId = $this->getAttribute(self::SOURCE_ID);

        $sourceType = $this->getAttribute(self::SOURCE_TYPE);

        $entity = E::getEntityClass($sourceType);

        $attributes[self::SOURCE] = $entity::getSignedId($sourceId);
    }

    public function setMode($mode)
    {
        return $this->setAttribute(self::MODE, $mode);
    }

    public function isBeneRegistrationRequired(): bool
    {
        if (($this->isRefund() === true) or
            ($this->hasVpa() === true) or
            ($this->isPennyTesting() === true))
        {
            return false;
        }

        return true;
    }

    public function shouldUseGateway(): bool
    {
        if ($this->hasVpa() === true)
        {
            return true;
        }

        if (($this->hasCard() === true) and
            ($this->card->getIssuer() === Issuer::ICIC))
        {
            return true;
        }

        return false;
    }
}
