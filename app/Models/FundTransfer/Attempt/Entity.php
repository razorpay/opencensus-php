<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\BankAccount;

class Entity extends Base\PublicEntity
{
    const SOURCE                 = 'source';
    const SOURCE_TYPE            = 'source_type';
    const SOURCE_ID              = 'source_id';
    const MERCHANT_ID            = 'merchant_id';
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

    protected $entity = 'fund_transfer_attempt';

    protected $fillable = [
        self::CHANNEL,
        self::VERSION,
        self::STATUS,
        self::NARRATION,
        self::BANK_STATUS_CODE,
        self::STATUS,
        self::REMARKS,
        self::FAILURE_REASON,
    ];

    protected $visible = [
        self::ID,
        self::SOURCE,
        self::MERCHANT_ID,
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

    public function getMode()
    {
        return $this->getAttribute(self::MODE);
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
     * @return boolean
     */
    public function isLatest()
    {
        $attempts = $this->source->fundTransferAttempts;

        if ($attempts->last()->getId() === $this->getId())
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
