<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\BankAccount;
use RZP\Models\Settlement\Channel;

class Entity extends Base\PublicEntity
{
    const SOURCE                 = 'source';
    const SOURCE_TYPE            = 'source_type';
    const SOURCE_ID              = 'source_id';
    const MERCHANT_ID            = 'merchant_id';
    const PURPOSE                = 'purpose';
    const BANK_ACCOUNT_ID        = 'bank_account_id';
    const BATCH_FUND_TRANSFER_ID = 'batch_fund_transfer_id';
    const CHANNEL                = 'channel';
    const VERSION                = 'version';
    const BANK_STATUS_CODE       = 'bank_status_code';
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

    protected $entity = 'fund_transfer_attempt';

    protected $fillable = [
        self::PURPOSE,
        self::CHANNEL,
        self::VERSION,
        self::STATUS,
        self::NARRATION,
        self::BANK_STATUS_CODE,
        self::STATUS,
        self::REMARKS,
        self::FAILURE_REASON,
        self::INITIATE_AT,
    ];

    protected $visible = [
        self::ID,
        self::SOURCE,
        self::MERCHANT_ID,
        self::PURPOSE,
        self::BANK_ACCOUNT_ID,
        self::BATCH_FUND_TRANSFER_ID,
        self::CHANNEL,
        self::VERSION,
        self::BANK_STATUS_CODE,
        self::MODE,
        self::STATUS,
        self::UTR,
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

    public function batchFundTransfer()
    {
        return $this->belongsTo('RZP\Models\FundTransfer\Batch\Entity');
    }

    // ------------------------------- getters ---------------------------------

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

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
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

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
    }

    public function isRefund()
    {
        return ($this->getAttribute(self::PURPOSE) === Purpose::REFUND);
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
}
